abstract final class Validators {
  static String? requiredField(String? value, {String label = 'Champ'}) {
    if (value == null || value.trim().isEmpty) {
      return '$label requis.';
    }

    return null;
  }

  static String? email(String? value) {
    final requiredError = requiredField(value, label: 'Adresse e-mail');
    if (requiredError != null) {
      return requiredError;
    }

    final emailPattern = RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$');
    if (!emailPattern.hasMatch(value!.trim())) {
      return 'Adresse e-mail invalide.';
    }

    return null;
  }

  static String? password(String? value) {
    final requiredError = requiredField(value, label: 'Mot de passe');
    if (requiredError != null) {
      return requiredError;
    }

    if (value!.trim().length < 8) {
      return 'Le mot de passe doit contenir au moins 8 caractères.';
    }

    return null;
  }
}
