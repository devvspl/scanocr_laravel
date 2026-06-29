import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../config/app_config.dart';
import '../models/bill_model.dart';
import '../providers/auth_provider.dart';
import '../services/api_service.dart';
import '../widgets/searchable_select.dart';
import 'bill_detail_screen.dart';

class BillListScreen extends StatefulWidget {
  const BillListScreen({super.key});

  @override
  State<BillListScreen> createState() => _BillListScreenState();
}

class _BillListScreenState extends State<BillListScreen> with SingleTickerProviderStateMixin {
  late TabController _tabCtrl;
  final _searchCtrl = TextEditingController();
  final _tabs = ['pending', 'approved', 'rejected', 'all'];
  final _tabLabels = ['Pending', 'Approved', 'Rejected', 'All'];

  Map<String, int> _counts = {'pending': 0, 'approved': 0, 'rejected': 0, 'all': 0};
  List<Bill> _bills = [];
  bool _loading = true;
  bool _loadingMore = false;
  bool _filtersVisible = false;
  int _page = 1;
  int _totalPages = 1;
  String _currentTab = 'pending';
  String _userName = '';

  // Filter data from API
  // (no longer pre-loaded — fetched on demand via SearchableSelect)

  // Selected filter values
  String? _selCompany;
  String? _selCompanyName;
  String? _selLocation;
  String? _selLocationName;
  String? _selFy;
  String? _selFyName;
  String? _selUser;
  String? _selUserName;
  String? _selFromDate;
  String? _selToDate;

  @override
  void initState() {
    super.initState();
    _tabCtrl = TabController(length: _tabs.length, vsync: this);
    _tabCtrl.addListener(() {
      if (!_tabCtrl.indexIsChanging) {
        _currentTab = _tabs[_tabCtrl.index];
        _page = 1;
        _loadBills();
      }
    });
    _loadUserName();
    _loadCounts();
    _loadBills();
  }

  Future<void> _loadUserName() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() => _userName = prefs.getString('user_name') ?? 'User');
  }

  Map<String, String> get _activeFilters => {
    if (_selCompany != null && _selCompany!.isNotEmpty) 'company_id': _selCompany!,
    if (_selFy != null && _selFy!.isNotEmpty) 'fy_id': _selFy!,
    if (_selLocation != null && _selLocation!.isNotEmpty) 'location_id': _selLocation!,
    if (_selUser != null && _selUser!.isNotEmpty) 'scanned_by': _selUser!,
    if (_selFromDate != null && _selFromDate!.isNotEmpty) 'from_date': _selFromDate!,
    if (_selToDate != null && _selToDate!.isNotEmpty) 'to_date': _selToDate!,
  };

  Future<void> _loadCounts() async {
    try {
      final counts = await ApiService.tabCounts(filters: _activeFilters);
      if (mounted) setState(() => _counts = counts);
    } on AuthException {
      _handleAuthError();
    } catch (_) {}
  }

  Future<void> _loadBills({bool loadMore = false}) async {
    if (!loadMore) setState(() { _loading = true; });
    else setState(() => _loadingMore = true);

    try {
      final result = await ApiService.listBills(
        tab: _currentTab,
        page: loadMore ? _page + 1 : 1,
        search: _searchCtrl.text,
        filters: _activeFilters,
      );

      final rows = (result['data'] as List).map((j) => Bill.fromJson(j)).toList();

      if (mounted) {
        setState(() {
          if (loadMore) { _bills.addAll(rows); _page++; }
          else { _bills = rows; _page = 1; }
          _totalPages = result['total_pages'] ?? 1;
          _loading = false;
          _loadingMore = false;
        });
      }
    } on AuthException {
      _handleAuthError();
    } catch (e) {
      if (mounted) setState(() { _loading = false; _loadingMore = false; });
    }
  }

  void _handleAuthError() { context.read<AuthProvider>().logout(); }

  Future<void> _refresh() async {
    await Future.wait([_loadCounts(), _loadBills()]);
  }

  void _openDetail(Bill bill) async {
    final result = await Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => BillDetailScreen(scanId: bill.scanId)),
    );
    if (result == true) _refresh();
  }

  void _resetFilters() {
    setState(() {
      _selCompany = null; _selCompanyName = null;
      _selFy = null; _selFyName = null;
      _selLocation = null; _selLocationName = null;
      _selUser = null; _selUserName = null;
      _selFromDate = null;
      _selToDate = null;
      _searchCtrl.clear();
    });
    _loadCounts();
    _loadBills();
  }

  Widget _buildDateField(String label, String? value, ValueChanged<String?> onChanged) {
    return SizedBox(
      height: 32,
      child: InkWell(
        onTap: () async {
          final picked = await showDatePicker(
            context: context,
            initialDate: value != null ? DateTime.tryParse(value) ?? DateTime.now() : DateTime.now(),
            firstDate: DateTime(2020),
            lastDate: DateTime.now().add(const Duration(days: 365)),
          );
          if (picked != null) {
            onChanged('${picked.year}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}');
          }
        },
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 8),
          decoration: BoxDecoration(
            border: Border.all(color: const Color(0xFFE7E5E4)),
            borderRadius: BorderRadius.circular(6),
            color: const Color(0xFFFAFAF9),
          ),
          child: Row(
            children: [
              const Icon(Icons.calendar_today, size: 12, color: Color(AppConfig.textSecondary)),
              const SizedBox(width: 4),
              Expanded(
                child: Text(
                  value ?? label,
                  style: TextStyle(fontSize: 11, color: value != null ? const Color(AppConfig.textPrimary) : const Color(AppConfig.textSecondary)),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  void dispose() { _tabCtrl.dispose(); _searchCtrl.dispose(); super.dispose(); }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();

    return Scaffold(
      backgroundColor: const Color(0xFFF5F5F4),
      body: SafeArea(
        child: Column(
          children: [
            // ═══ App Header ═══
            Container(
              color: const Color(AppConfig.primaryColor),
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              child: Column(
                children: [
                  // Top row: Logo + Title + Actions
                  Row(
                    children: [
                      Container(
                        width: 30, height: 32,
                        decoration: BoxDecoration(
                          color: Colors.white.withAlpha((0.15 * 255).toInt()),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Icon(Icons.document_scanner_rounded, size: 18, color: Colors.white),
                      ),
                      const SizedBox(width: 10),
                      const Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('ScanOCR', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: Colors.white)),
                            Text('Bill Approval', style: TextStyle(fontSize: 11, color: Colors.white70)),
                          ],
                        ),
                      ),
                      // User name badge
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.white.withAlpha((0.15 * 255).toInt()),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.person_outline, size: 14, color: Colors.white70),
                            const SizedBox(width: 4),
                            Text(_userName, style: const TextStyle(fontSize: 11, color: Colors.white, fontWeight: FontWeight.w500)),
                          ],
                        ),
                      ),
                      const SizedBox(width: 8),
                      // Logout
                      InkWell(
                        onTap: () => auth.logout(),
                        child: Container(
                          padding: const EdgeInsets.all(6),
                          decoration: BoxDecoration(
                            color: Colors.white.withAlpha((0.1 * 255).toInt()),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: const Icon(Icons.logout_rounded, size: 18, color: Colors.white70),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),

                  // Tabs
                  Container(
                    height: 36,
                    decoration: BoxDecoration(
                      color: Colors.white.withAlpha((0.1 * 255).toInt()),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: TabBar(
                      controller: _tabCtrl,
                      indicator: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      labelColor: const Color(AppConfig.primaryColor),
                      unselectedLabelColor: Colors.white70,
                      labelStyle: const TextStyle(fontSize: 10, fontWeight: FontWeight.w700),
                      unselectedLabelStyle: const TextStyle(fontSize: 10),
                      indicatorSize: TabBarIndicatorSize.tab,
                      dividerColor: Colors.transparent,
                      tabs: List.generate(_tabs.length, (i) => Tab(
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text(_tabLabels[i]),
                            const SizedBox(width: 3),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                              decoration: BoxDecoration(
                                color: _tabCtrl.index == i
                                    ? const Color(AppConfig.primaryColor).withAlpha((0.15 * 255).toInt())
                                    : Colors.white.withAlpha((0.2 * 255).toInt()),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Text(
                                '${_counts[_tabs[i]] ?? 0}',
                                style: TextStyle(
                                  fontSize: 10, fontWeight: FontWeight.w700,
                                  color: _tabCtrl.index == i ? const Color(AppConfig.primaryColor) : Colors.white70,
                                ),
                              ),
                            ),
                          ],
                        ),
                      )),
                    ),
                  ),
                ],
              ),
            ),

            // ═══ Search + Filter Toggle ═══
            Container(
              padding: const EdgeInsets.fromLTRB(12, 10, 12, 6),
              color: Colors.white,
              child: Row(
                children: [
                  Expanded(
                    child: SizedBox(
                      height: 36,
                      child: TextField(
                        controller: _searchCtrl,
                        style: const TextStyle(fontSize: 12),
                        decoration: InputDecoration(
                          hintText: 'Search vendor, bill no, company...',
                          hintStyle: const TextStyle(fontSize: 12),
                          prefixIcon: const Icon(Icons.search, size: 18),
                          suffixIcon: _searchCtrl.text.isNotEmpty
                              ? IconButton(icon: const Icon(Icons.close, size: 16), onPressed: () { _searchCtrl.clear(); _loadBills(); })
                              : null,
                          contentPadding: const EdgeInsets.symmetric(horizontal: 12),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFFE7E5E4))),
                          enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFFE7E5E4))),
                          filled: true,
                          fillColor: const Color(0xFFFAFAF9),
                        ),
                        onSubmitted: (_) => _loadBills(),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  // Filter toggle button
                  InkWell(
                    onTap: () => setState(() => _filtersVisible = !_filtersVisible),
                    child: Container(
                      height: 36,
                      padding: const EdgeInsets.symmetric(horizontal: 10),
                      decoration: BoxDecoration(
                        color: _filtersVisible ? const Color(AppConfig.primaryColor) : const Color(0xFFFAFAF9),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: _filtersVisible ? const Color(AppConfig.primaryColor) : const Color(0xFFE7E5E4)),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.filter_list_rounded, size: 16, color: _filtersVisible ? Colors.white : const Color(AppConfig.textSecondary)),
                          const SizedBox(width: 4),
                          Text('Filters', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: _filtersVisible ? Colors.white : const Color(AppConfig.textSecondary))),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  // Apply search
                  InkWell(
                    onTap: () => _loadBills(),
                    child: Container(
                      height: 36, width: 36,
                      decoration: BoxDecoration(
                        color: const Color(AppConfig.primaryColor),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Icon(Icons.search, size: 18, color: Colors.white),
                    ),
                  ),
                ],
              ),
            ),

            // ═══ Filters Panel (collapsible) ═══
            AnimatedCrossFade(
              firstChild: const SizedBox(width: double.infinity),
              secondChild: Container(
                padding: const EdgeInsets.fromLTRB(12, 6, 12, 12),
                color: Colors.white,
                child: Column(
                  children: [
                    const Divider(height: 1, color: Color(0xFFE7E5E4)),
                    const SizedBox(height: 10),
                    // Row 1: Company + FY
                    Row(
                      children: [
                        Expanded(child: SearchableSelect(
                          label: 'Company', endpoint: 'companies',
                          selectedId: _selCompany, selectedName: _selCompanyName,
                          onChanged: (v) => setState(() { _selCompany = v.key; _selCompanyName = v.value; }),
                        )),
                        const SizedBox(width: 8),
                        Expanded(child: SearchableSelect(
                          label: 'Financial Year', endpoint: 'financial-years',
                          selectedId: _selFy, selectedName: _selFyName,
                          onChanged: (v) => setState(() { _selFy = v.key; _selFyName = v.value; }),
                        )),
                      ],
                    ),
                    const SizedBox(height: 8),
                    // Row 2: Location + Scanned By
                    Row(
                      children: [
                        Expanded(child: SearchableSelect(
                          label: 'Location', endpoint: 'locations',
                          selectedId: _selLocation, selectedName: _selLocationName,
                          onChanged: (v) => setState(() { _selLocation = v.key; _selLocationName = v.value; }),
                        )),
                        const SizedBox(width: 8),
                        Expanded(child: SearchableSelect(
                          label: 'Scanned By', endpoint: 'users',
                          selectedId: _selUser, selectedName: _selUserName,
                          onChanged: (v) => setState(() { _selUser = v.key; _selUserName = v.value; }),
                        )),
                      ],
                    ),
                    const SizedBox(height: 8),
                    // Row 3: Dates + Apply/Reset
                    Row(
                      children: [
                        Expanded(child: _buildDateField('From', _selFromDate, (v) => setState(() => _selFromDate = v))),
                        const SizedBox(width: 8),
                        Expanded(child: _buildDateField('To', _selToDate, (v) => setState(() => _selToDate = v))),
                        const SizedBox(width: 8),
                        SizedBox(
                          height: 32,
                          child: ElevatedButton.icon(
                            onPressed: () { _loadCounts(); _loadBills(); },
                            icon: const Icon(Icons.filter_alt_outlined, size: 14),
                            label: const Text('Apply', style: TextStyle(fontSize: 11)),
                            style: ElevatedButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 10)),
                          ),
                        ),
                        const SizedBox(width: 6),
                        SizedBox(
                          height: 32,
                          child: OutlinedButton(
                            onPressed: _resetFilters,
                            style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 8)),
                            child: const Text('Reset', style: TextStyle(fontSize: 11)),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              crossFadeState: _filtersVisible ? CrossFadeState.showSecond : CrossFadeState.showFirst,
              duration: const Duration(milliseconds: 200),
            ),

            // ═══ Bill List ═══
            Expanded(
              child: _loading
                  ? const Center(child: CircularProgressIndicator(color: Color(AppConfig.primaryColor)))
                  : _bills.isEmpty
                      ? Center(
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(Icons.inbox_outlined, size: 56, color: Colors.grey.shade300),
                              const SizedBox(height: 12),
                              Text('No bills found', style: TextStyle(color: Colors.grey.shade500, fontSize: 14)),
                              const SizedBox(height: 4),
                              Text('Pull down to refresh', style: TextStyle(color: Colors.grey.shade400, fontSize: 12)),
                            ],
                          ),
                        )
                      : RefreshIndicator(
                          onRefresh: _refresh,
                          color: const Color(AppConfig.primaryColor),
                          child: NotificationListener<ScrollNotification>(
                            onNotification: (scroll) {
                              if (scroll.metrics.pixels >= scroll.metrics.maxScrollExtent - 100 &&
                                  !_loadingMore && _page < _totalPages) {
                                _loadBills(loadMore: true);
                              }
                              return false;
                            },
                            child: ListView.builder(
                              padding: const EdgeInsets.all(12),
                              itemCount: _bills.length + (_loadingMore ? 1 : 0),
                              itemBuilder: (ctx, i) {
                                if (i == _bills.length) {
                                  return const Padding(padding: EdgeInsets.all(16), child: Center(child: CircularProgressIndicator(strokeWidth: 2)));
                                }
                                return _BillCard(bill: _bills[i], onTap: () => _openDetail(_bills[i]));
                              },
                            ),
                          ),
                        ),
            ),
          ],
        ),
      ),
    );
  }
}

// ═══ Filter Helper Methods (inside State via extension won't work, use mixin-like approach) ═══

// ═══ Bill Card Widget ═══
class _BillCard extends StatelessWidget {
  final Bill bill;
  final VoidCallback onTap;
  const _BillCard({required this.bill, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              // File icon
              Container(
                width: 40, height: 40,
                decoration: BoxDecoration(
                  color: const Color(0xFFFEF2F2),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(
                  bill.fileExt == 'pdf' ? Icons.picture_as_pdf_rounded : Icons.image_outlined,
                  size: 20, color: const Color(AppConfig.primaryColor),
                ),
              ),
              const SizedBox(width: 12),

              // Content
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      bill.documentName,
                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(AppConfig.textPrimary)),
                      maxLines: 1, overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 3),
                    Row(
                      children: [
                        if (bill.vendor != null) ...[
                          Icon(Icons.store_outlined, size: 11, color: Colors.grey.shade500),
                          const SizedBox(width: 3),
                          Flexible(child: Text(bill.vendor!, style: TextStyle(fontSize: 11, color: Colors.grey.shade600), overflow: TextOverflow.ellipsis)),
                          const SizedBox(width: 8),
                        ],
                        if (bill.billDate != null) ...[
                          Icon(Icons.calendar_today_outlined, size: 10, color: Colors.grey.shade500),
                          const SizedBox(width: 3),
                          Text(bill.billDate!, style: TextStyle(fontSize: 10, color: Colors.grey.shade500)),
                        ],
                      ],
                    ),
                    if (bill.company != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 2),
                        child: Text(bill.company!, style: TextStyle(fontSize: 10, color: Colors.grey.shade400)),
                      ),
                  ],
                ),
              ),

              // Status + Arrow
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  _StatusBadge(status: bill.status),
                  const SizedBox(height: 6),
                  Icon(Icons.chevron_right, size: 18, color: Colors.grey.shade300),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  final String status;
  const _StatusBadge({required this.status});

  @override
  Widget build(BuildContext context) {
    final (Color bg, Color fg, String label) = switch (status) {
      'approved' => (const Color(0xFFDCFCE7), const Color(0xFF166534), 'Approved'),
      'rejected' => (const Color(0xFFFEE2E2), const Color(0xFF991B1B), 'Rejected'),
      _ => (const Color(0xFFFEF3C7), const Color(0xFF92400E), 'Pending'),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(5)),
      child: Text(label, style: TextStyle(fontSize: 9, fontWeight: FontWeight.w700, color: fg)),
    );
  }
}
