import 'package:flutter/material.dart';
import 'package:syncfusion_flutter_pdfviewer/pdfviewer.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:cherry_toast/cherry_toast.dart';
import 'package:cherry_toast/resources/arrays.dart';
import '../config/app_config.dart';
import '../models/bill_model.dart';
import '../services/api_service.dart';

class BillDetailScreen extends StatefulWidget {
  final int scanId;
  const BillDetailScreen({super.key, required this.scanId});

  @override
  State<BillDetailScreen> createState() => _BillDetailScreenState();
}

class _BillDetailScreenState extends State<BillDetailScreen> {
  BillDetail? _detail;
  bool _loading = true;
  bool _actionLoading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadDetail();
  }

  Future<void> _loadDetail() async {
    try {
      final detail = await ApiService.billDetail(widget.scanId);
      if (mounted) setState(() { _detail = detail; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString(); _loading = false; });
    }
  }

  Future<void> _approve() async {
    final remark = await _showActionSheet('Approve Bill', 'Add remark (optional)', false);
    if (remark == null) return;
    setState(() => _actionLoading = true);
    try {
      final result = await ApiService.approveBill(widget.scanId, remark: remark.isEmpty ? null : remark);
      if (mounted) {
        CherryToast.success(
          title: Text(result['message'] ?? 'Bill Approved', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
          borderRadius: 10,
          animationType: AnimationType.fromTop,
          toastDuration: const Duration(seconds: 3),
        ).show(context);
        Future.delayed(const Duration(milliseconds: 500), () { if (mounted) Navigator.pop(context, true); });
      }
    } catch (e) {
      if (mounted) { setState(() => _actionLoading = false); _showErrorToast('Error: $e'); }
    }
  }

  Future<void> _reject() async {
    final reason = await _showActionSheet('Reject Bill', 'Enter rejection reason *', true);
    if (reason == null || reason.isEmpty) return;
    setState(() => _actionLoading = true);
    try {
      final result = await ApiService.rejectBill(widget.scanId, reason);
      if (mounted) {
        CherryToast.error(
          title: Text(result['message'] ?? 'Bill Rejected', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
          borderRadius: 10,
          animationType: AnimationType.fromTop,
          toastDuration: const Duration(seconds: 3),
        ).show(context);
        Future.delayed(const Duration(milliseconds: 500), () { if (mounted) Navigator.pop(context, true); });
      }
    } catch (e) {
      if (mounted) { setState(() => _actionLoading = false); _showErrorToast('Error: $e'); }
    }
  }

  void _showErrorToast(String msg) {
    CherryToast.error(
      title: Text(msg, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
      borderRadius: 10,
      animationType: AnimationType.fromTop,
      toastDuration: const Duration(seconds: 4),
    ).show(context);
  }

  Future<String?> _showActionSheet(String title, String hint, bool required) {
    final ctrl = TextEditingController();
    return showModalBottomSheet<String>(
      context: context, isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(16))),
      builder: (ctx) => Padding(
        padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom, left: 20, right: 20, top: 20),
        child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(title, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
          const SizedBox(height: 14),
          TextField(controller: ctrl, maxLines: 3, autofocus: true, decoration: InputDecoration(hintText: hint)),
          const SizedBox(height: 16),
          Row(children: [
            Expanded(child: OutlinedButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel'))),
            const SizedBox(width: 12),
            Expanded(child: OutlinedButton(
              onPressed: () { if (required && ctrl.text.trim().isEmpty) return; Navigator.pop(ctx, ctrl.text.trim()); },
              style: ElevatedButton.styleFrom(backgroundColor: title.contains('Approve') ? const Color(AppConfig.successColor) : const Color(AppConfig.dangerColor)),
              child: Text(title.contains('Approve') ? 'Approve' : 'Reject', style: TextStyle(color:Colors.white)),
            )),
          ]),
          const SizedBox(height: 20),
        ]),
      ),
    );
  }

  void _openExternal(String? url) async {
    if (url == null) return;
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F5F4),
      appBar: AppBar(
        title: Text('Scan #${widget.scanId}', style: const TextStyle(fontSize: 15)),
        actions: [
          if (_detail?.fileUrl != null)
            IconButton(icon: const Icon(Icons.open_in_new, size: 20), onPressed: () => _openExternal(_detail!.fileUrl), tooltip: 'Open externally'),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: Color(AppConfig.primaryColor)))
          : _error != null
              ? Center(child: Text(_error!, style: const TextStyle(color: Color(AppConfig.dangerColor))))
              : _buildSplitView(),
      bottomNavigationBar: _detail != null && _detail!.status == 'pending' ? _buildActions() : null,
    );
  }

  Widget _buildSplitView() {
    final d = _detail!;
    return Column(
      children: [
        // ═══ Document Viewer (top ~55%) ═══
        Expanded(
          flex: 55,
          child: Container(
            margin: const EdgeInsets.fromLTRB(8, 8, 8, 0),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: const Color(0xFFE7E5E4)),
            ),
            clipBehavior: Clip.antiAlias,
            child: _buildDocViewer(d.fileUrl, d.fileExt),
          ),
        ),

        // ═══ Details Section (bottom ~45%) ═══
        Expanded(
          flex: 45,
          child: Container(
            margin: const EdgeInsets.fromLTRB(8, 6, 8, 8),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: const Color(0xFFE7E5E4)),
            ),
            child: _buildDetailsPanel(d),
          ),
        ),
      ],
    );
  }

  Widget _buildDocViewer(String? url, String? ext) {
    if (url == null || url.isEmpty) {
      return const Center(child: Text('No document', style: TextStyle(color: Color(AppConfig.textSecondary))));
    }
    final isPdf = (ext ?? '').toLowerCase() == 'pdf' || url.toLowerCase().endsWith('.pdf');
    if (isPdf) {
      return SfPdfViewer.network(url, canShowScrollHead: true, canShowPaginationDialog: false, pageSpacing: 2);
    }
    return InteractiveViewer(
      minScale: 0.5, maxScale: 4.0,
      child: CachedNetworkImage(
        imageUrl: url, fit: BoxFit.contain,
        placeholder: (_, __) => const Center(child: CircularProgressIndicator(strokeWidth: 2)),
        errorWidget: (_, __, ___) => Column(mainAxisAlignment: MainAxisAlignment.center, children: [
          const Icon(Icons.broken_image_outlined, size: 40, color: Color(AppConfig.textSecondary)),
          const SizedBox(height: 8),
          TextButton.icon(onPressed: () => _openExternal(url), icon: const Icon(Icons.open_in_new, size: 14), label: const Text('Open externally', style: TextStyle(fontSize: 12))),
        ]),
      ),
    );
  }

  Widget _buildDetailsPanel(BillDetail d) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header row: Document name + status badge
          Row(
            children: [
              Expanded(
                child: Text(d.documentName, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: Color(AppConfig.textPrimary)), maxLines: 2, overflow: TextOverflow.ellipsis),
              ),
              const SizedBox(width: 8),
              _StatusBadge(status: d.status),
            ],
          ),
          if (d.remark != null && d.remark!.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 4),
              child: Text('Remark: ${d.remark}', style: const TextStyle(fontSize: 11, color: Color(AppConfig.textSecondary), fontStyle: FontStyle.italic)),
            ),
          const Divider(height: 16),

          // Bill details — shown directly, no toggle
          _row(Icons.store_outlined, 'Vendor', d.vendor),
          _row(Icons.receipt_outlined, 'Bill No', d.billNo),
          _row(Icons.calendar_today_outlined, 'Bill Date', d.billDate),
          _row(Icons.business_outlined, 'Company', d.company),
          _row(Icons.location_on_outlined, 'Location', d.location),
          _row(Icons.date_range_outlined, 'FY', d.fy),
          _row(Icons.person_outline, 'Scanned By', d.scannedBy),
          _row(Icons.access_time, 'Scan Date', d.scanDate),
          if (d.approvalDate != null)
            _row(Icons.event_available, 'Action Date', d.approvalDate),

          // Support files
          if (d.supports.isNotEmpty) ...[
            const Divider(height: 18),
            Text('Supporting Files (${d.supports.length})', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
            const SizedBox(height: 6),
            ...d.supports.map((s) => InkWell(
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => _FileViewerPage(url: s.fileUrl ?? '', title: s.docType ?? s.file, isPdf: (s.fileExt ?? '').toLowerCase() == 'pdf'))),
              child: Container(
                margin: const EdgeInsets.only(bottom: 6),
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                decoration: BoxDecoration(color: const Color(0xFFFAFAF9), borderRadius: BorderRadius.circular(6), border: Border.all(color: const Color(0xFFE7E5E4))),
                child: Row(children: [
                  Icon((s.fileExt ?? '').toLowerCase() == 'pdf' ? Icons.picture_as_pdf : Icons.image, size: 16, color: const Color(AppConfig.primaryColor)),
                  const SizedBox(width: 8),
                  Expanded(child: Text(s.docType ?? s.file, style: const TextStyle(fontSize: 11), overflow: TextOverflow.ellipsis)),
                  const Icon(Icons.chevron_right, size: 16, color: Color(AppConfig.textSecondary)),
                ]),
              ),
            )),
          ],
        ],
      ),
    );
  }

  Widget _row(IconData icon, String label, String? value) {
    if (value == null || value.isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(children: [
        Icon(icon, size: 15, color: const Color(AppConfig.textSecondary)),
        const SizedBox(width: 8),
        SizedBox(width: 75, child: Text(label, style: const TextStyle(fontSize: 11, color: Color(AppConfig.textSecondary)))),
        Expanded(child: Text(value, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w500, color: Color(AppConfig.textPrimary)))),
      ]),
    );
  }

  Widget _buildActions() {
    return Container(
      padding: const EdgeInsets.fromLTRB(12, 10, 12, 14),
      decoration: const BoxDecoration(color: Colors.white, border: Border(top: BorderSide(color: Color(0xFFE7E5E4)))),
      child: Row(children: [
        Expanded(child: SizedBox(height: 44, child: OutlinedButton.icon(
          onPressed: _actionLoading ? null : _reject,
          icon: const Icon(Icons.close_rounded, size: 18),
          label: const Text('Reject'),
          style: OutlinedButton.styleFrom(foregroundColor: const Color(AppConfig.dangerColor), side: const BorderSide(color: Color(AppConfig.dangerColor)), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10))),
        ))),
        const SizedBox(width: 12),
        Expanded(child: SizedBox(height: 44, child: ElevatedButton.icon(
          onPressed: _actionLoading ? null : _approve,
          icon: _actionLoading ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Icon(Icons.check_rounded, size: 18),
          label: const Text('Approve'),
          style: ElevatedButton.styleFrom(backgroundColor: const Color(AppConfig.successColor), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10))),
        ))),
      ]),
    );
  }
}

// ═══ Status Badge ═══
class _StatusBadge extends StatelessWidget {
  final String status;
  const _StatusBadge({required this.status});

  @override
  Widget build(BuildContext context) {
    final (Color bg, Color fg, IconData icon, String label) = switch (status) {
      'approved' => (const Color(0xFFDCFCE7), const Color(0xFF166534), Icons.check_circle, 'Approved'),
      'rejected' => (const Color(0xFFFEE2E2), const Color(0xFF991B1B), Icons.cancel, 'Rejected'),
      _ => (const Color(0xFFFEF3C7), const Color(0xFF92400E), Icons.schedule, 'Pending'),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(6)),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        Icon(icon, size: 13, color: fg),
        const SizedBox(width: 4),
        Text(label, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: fg)),
      ]),
    );
  }
}

// ═══ Full-screen File Viewer ═══
class _FileViewerPage extends StatelessWidget {
  final String url;
  final String title;
  final bool isPdf;
  const _FileViewerPage({required this.url, required this.title, required this.isPdf});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(title, style: const TextStyle(fontSize: 14)),
        actions: [
          IconButton(icon: const Icon(Icons.open_in_new, size: 20), onPressed: () async {
            final uri = Uri.parse(url);
            if (await canLaunchUrl(uri)) await launchUrl(uri, mode: LaunchMode.externalApplication);
          }),
        ],
      ),
      body: isPdf
          ? SfPdfViewer.network(url, canShowScrollHead: true)
          : InteractiveViewer(minScale: 0.5, maxScale: 4.0, child: CachedNetworkImage(imageUrl: url, fit: BoxFit.contain, placeholder: (_, __) => const Center(child: CircularProgressIndicator(strokeWidth: 2)), errorWidget: (_, __, ___) => const Center(child: Icon(Icons.broken_image_outlined, size: 48)))),
    );
  }
}
