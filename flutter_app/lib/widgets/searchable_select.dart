import 'dart:async';
import 'package:flutter/material.dart';
import '../config/app_config.dart';
import '../services/api_service.dart';

/// A searchable bottom sheet selector with infinite scroll.
/// Fetches data from API on focus/open with pagination.
class SearchableSelect extends StatelessWidget {
  final String label;
  final String endpoint; // e.g. 'companies', 'locations', 'financial-years', 'users'
  final String? selectedId;
  final String? selectedName;
  final ValueChanged<MapEntry<String?, String?>> onChanged;

  const SearchableSelect({
    super.key,
    required this.label,
    required this.endpoint,
    this.selectedId,
    this.selectedName,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () async {
        final result = await showModalBottomSheet<MapEntry<String?, String?>>(
          context: context,
          isScrollControlled: true,
          shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(14))),
          builder: (_) => _SearchableSheet(label: label, endpoint: endpoint, selectedId: selectedId),
        );
        if (result != null) onChanged(result);
      },
      child: Container(
        height: 34,
        padding: const EdgeInsets.symmetric(horizontal: 10),
        decoration: BoxDecoration(
          border: Border.all(color: const Color(0xFFE7E5E4)),
          borderRadius: BorderRadius.circular(7),
          color: const Color(0xFFFAFAF9),
        ),
        child: Row(
          children: [
            Expanded(
              child: Text(
                selectedName ?? label,
                style: TextStyle(
                  fontSize: 11,
                  color: selectedName != null ? const Color(AppConfig.textPrimary) : const Color(AppConfig.textSecondary),
                  fontWeight: selectedName != null ? FontWeight.w500 : FontWeight.normal,
                ),
                overflow: TextOverflow.ellipsis,
              ),
            ),
            if (selectedId != null)
              GestureDetector(
                onTap: () => onChanged(const MapEntry(null, null)),
                child: const Icon(Icons.close, size: 14, color: Color(AppConfig.textSecondary)),
              )
            else
              const Icon(Icons.keyboard_arrow_down, size: 16, color: Color(AppConfig.textSecondary)),
          ],
        ),
      ),
    );
  }
}

class _SearchableSheet extends StatefulWidget {
  final String label;
  final String endpoint;
  final String? selectedId;

  const _SearchableSheet({required this.label, required this.endpoint, this.selectedId});

  @override
  State<_SearchableSheet> createState() => _SearchableSheetState();
}

class _SearchableSheetState extends State<_SearchableSheet> {
  final _searchCtrl = TextEditingController();
  final _scrollCtrl = ScrollController();
  List<Map<String, dynamic>> _items = [];
  bool _loading = true;
  bool _loadingMore = false;
  bool _hasMore = true;
  int _page = 1;
  Timer? _debounce;

  @override
  void initState() {
    super.initState();
    _loadData();
    _scrollCtrl.addListener(_onScroll);
  }

  void _onScroll() {
    if (_scrollCtrl.position.pixels >= _scrollCtrl.position.maxScrollExtent - 50 && !_loadingMore && _hasMore) {
      _loadMore();
    }
  }

  Future<void> _loadData() async {
    setState(() { _loading = true; _page = 1; });
    try {
      final result = await ApiService.fetchFilterData(widget.endpoint, q: _searchCtrl.text, page: 1);
      if (mounted) setState(() { _items = result['items']; _hasMore = result['has_more']; _loading = false; });
    } catch (_) {
      if (mounted) setState(() { _loading = false; });
    }
  }

  Future<void> _loadMore() async {
    setState(() => _loadingMore = true);
    try {
      final result = await ApiService.fetchFilterData(widget.endpoint, q: _searchCtrl.text, page: _page + 1);
      if (mounted) setState(() { _items.addAll(result['items']); _hasMore = result['has_more']; _page++; _loadingMore = false; });
    } catch (_) {
      if (mounted) setState(() => _loadingMore = false);
    }
  }

  void _onSearch(String val) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 300), () => _loadData());
  }

  @override
  void dispose() { _searchCtrl.dispose(); _scrollCtrl.dispose(); _debounce?.cancel(); super.dispose(); }

  @override
  Widget build(BuildContext context) {
    return Container(
      height: MediaQuery.of(context).size.height * 0.6,
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
      child: Column(
        children: [
          // Handle bar
          Center(child: Container(width: 36, height: 4, decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)))),
          const SizedBox(height: 12),

          // Title + "All" option
          Row(
            children: [
              Text('Select ${widget.label}', style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
              const Spacer(),
              TextButton(
                onPressed: () => Navigator.pop(context, const MapEntry(null, null)),
                child: const Text('Clear', style: TextStyle(fontSize: 12)),
              ),
            ],
          ),
          const SizedBox(height: 8),

          // Search
          SizedBox(
            height: 38,
            child: TextField(
              controller: _searchCtrl,
              onChanged: _onSearch,
              style: const TextStyle(fontSize: 13),
              decoration: InputDecoration(
                hintText: 'Search ${widget.label.toLowerCase()}...',
                hintStyle: const TextStyle(fontSize: 12),
                prefixIcon: const Icon(Icons.search, size: 18),
                contentPadding: const EdgeInsets.symmetric(horizontal: 12),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFFE7E5E4))),
                enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFFE7E5E4))),
              ),
            ),
          ),
          const SizedBox(height: 8),

          // List
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator(strokeWidth: 2, color: Color(AppConfig.primaryColor)))
                : _items.isEmpty
                    ? Center(child: Text('No ${widget.label.toLowerCase()} found', style: TextStyle(fontSize: 13, color: Colors.grey.shade500)))
                    : ListView.builder(
                        controller: _scrollCtrl,
                        itemCount: _items.length + (_loadingMore ? 1 : 0),
                        itemBuilder: (ctx, i) {
                          if (i == _items.length) {
                            return const Padding(padding: EdgeInsets.all(12), child: Center(child: CircularProgressIndicator(strokeWidth: 2)));
                          }
                          final item = _items[i];
                          final id = item['id'].toString();
                          final name = item['name']?.toString() ?? '';
                          final isSelected = id == widget.selectedId;

                          return InkWell(
                            onTap: () => Navigator.pop(context, MapEntry(id, name)),
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
                              decoration: BoxDecoration(
                                color: isSelected ? const Color(AppConfig.primaryLight) : null,
                                border: Border(bottom: BorderSide(color: Colors.grey.shade100)),
                              ),
                              child: Row(
                                children: [
                                  Expanded(child: Text(name, style: TextStyle(fontSize: 13, fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal, color: const Color(AppConfig.textPrimary)))),
                                  if (isSelected) const Icon(Icons.check_circle, size: 18, color: Color(AppConfig.primaryColor)),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
          ),
        ],
      ),
    );
  }
}
