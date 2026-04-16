class UserModel {
  const UserModel({
    required this.id,
    required this.fullName,
    required this.email,
    required this.role,
    this.firstName,
    this.lastName,
    this.phone,
    this.dateOfBirth,
    this.gender,
    this.address,
    this.avatarUrl,
    this.isActive = true,
  });

  final String id;
  final String fullName;
  final String email;
  final String role;
  final String? firstName;
  final String? lastName;
  final String? phone;
  final String? dateOfBirth;
  final String? gender;
  final String? address;
  final String? avatarUrl;
  final bool isActive;

  factory UserModel.fromJson(Map<String, dynamic> json) {
    final roles = (json['roles'] as List<dynamic>?)?.cast<String>() ?? ['ROLE_PATIENT'];
    final primaryRole = roles.firstWhere(
      (r) => r != 'ROLE_USER',
      orElse: () => roles.isNotEmpty ? roles.first : 'ROLE_PATIENT',
    );
    final firstName = json['firstName'] as String?;
    final lastName = json['lastName'] as String?;
    final fullName = json['fullName'] as String? ??
        [firstName, lastName].where((p) => p != null && p.isNotEmpty).join(' ');

    return UserModel(
      id: json['id'].toString(),
      fullName: fullName,
      email: json['email'] as String? ?? '',
      role: primaryRole,
      firstName: firstName,
      lastName: lastName,
      phone: json['phone'] as String?,
      dateOfBirth: json['dateOfBirth'] as String?,
      gender: json['gender'] as String?,
      address: json['address'] as String?,
      avatarUrl: json['avatarUrl'] as String?,
      isActive: json['isActive'] as bool? ?? true,
    );
  }
}
