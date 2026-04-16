class NotificationModel {
  const NotificationModel({
    required this.id,
    required this.title,
    required this.body,
    required this.createdAt,
    required this.type,
    required this.isRead,
    this.typeLabel,
    this.referenceId,
    this.readAt,
  });

  final String id;
  final String title;
  final String body;
  final DateTime createdAt;
  final String type;
  final bool isRead;
  final String? typeLabel;
  final String? referenceId;
  final DateTime? readAt;

  factory NotificationModel.fromJson(Map<String, dynamic> json) {
    return NotificationModel(
      id: json['id'].toString(),
      title: json['title'] as String? ?? '',
      // API field is "message"; keep "body" as the local name for UI.
      body: json['message'] as String? ?? '',
      createdAt: DateTime.tryParse(json['createdAt'] as String? ?? '') ?? DateTime.now(),
      type: json['type'] as String? ?? '',
      typeLabel: json['typeLabel'] as String?,
      isRead: json['isRead'] as bool? ?? false,
      referenceId: json['referenceId']?.toString(),
      readAt: json['readAt'] != null
          ? DateTime.tryParse(json['readAt'] as String)
          : null,
    );
  }
}
