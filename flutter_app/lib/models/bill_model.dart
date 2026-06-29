class Bill {
  final int scanId;
  final String documentName;
  final String? fileUrl;
  final String? fileExt;
  final String? billDate;
  final String? billNo;
  final String? company;
  final String? location;
  final String? vendor;
  final String? scannedBy;
  final String? scanDate;
  final String status;
  final String? remark;
  final String? approvalDate;

  Bill({
    required this.scanId,
    required this.documentName,
    this.fileUrl,
    this.fileExt,
    this.billDate,
    this.billNo,
    this.company,
    this.location,
    this.vendor,
    this.scannedBy,
    this.scanDate,
    required this.status,
    this.remark,
    this.approvalDate,
  });

  factory Bill.fromJson(Map<String, dynamic> json) {
    return Bill(
      scanId: json['scan_id'] ?? 0,
      documentName: json['document_name'] ?? 'Unnamed',
      fileUrl: json['file_url'],
      fileExt: json['file_ext'],
      billDate: json['bill_date'],
      billNo: json['bill_no'],
      company: json['company'],
      location: json['location'],
      vendor: json['vendor'],
      scannedBy: json['scanned_by'],
      scanDate: json['scan_date'],
      status: json['status'] ?? 'pending',
      remark: json['remark'],
      approvalDate: json['approval_date'],
    );
  }
}

class BillDetail extends Bill {
  final String? fy;
  final List<SupportFile> supports;

  BillDetail({
    required super.scanId,
    required super.documentName,
    super.fileUrl,
    super.fileExt,
    super.billDate,
    super.billNo,
    super.company,
    super.location,
    super.vendor,
    super.scannedBy,
    super.scanDate,
    required super.status,
    super.remark,
    super.approvalDate,
    this.fy,
    this.supports = const [],
  });

  factory BillDetail.fromJson(Map<String, dynamic> json) {
    return BillDetail(
      scanId: json['scan_id'] ?? 0,
      documentName: json['document_name'] ?? 'Unnamed',
      fileUrl: json['file_url'],
      fileExt: json['file_ext'],
      billDate: json['bill_date'],
      billNo: json['bill_no'],
      company: json['company'],
      location: json['location'],
      vendor: json['vendor'],
      scannedBy: json['scanned_by'],
      scanDate: json['scan_date'],
      status: json['status'] ?? 'pending',
      remark: json['remark'],
      approvalDate: json['approval_date'],
      fy: json['fy'],
      supports: (json['supports'] as List<dynamic>? ?? [])
          .map((s) => SupportFile.fromJson(s))
          .toList(),
    );
  }
}

class SupportFile {
  final int id;
  final String file;
  final String? fileExt;
  final String? fileUrl;
  final String? docType;

  SupportFile({
    required this.id,
    required this.file,
    this.fileExt,
    this.fileUrl,
    this.docType,
  });

  factory SupportFile.fromJson(Map<String, dynamic> json) {
    return SupportFile(
      id: json['id'] ?? 0,
      file: json['file'] ?? '',
      fileExt: json['file_ext'],
      fileUrl: json['file_url'],
      docType: json['doc_type'],
    );
  }
}
