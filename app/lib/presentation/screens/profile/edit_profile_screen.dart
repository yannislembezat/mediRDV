import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/constants/app_sizes.dart';
import '../../../core/utils/validators.dart';
import '../../providers/auth_provider.dart';
import '../../providers/profile_provider.dart';
import '../../widgets/app_bottom_navigation.dart';
import '../../widgets/app_scaffold.dart';

class EditProfileScreen extends ConsumerStatefulWidget {
  const EditProfileScreen({super.key});

  @override
  ConsumerState<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends ConsumerState<EditProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _firstNameController;
  late final TextEditingController _lastNameController;
  late final TextEditingController _emailController;
  late final TextEditingController _phoneController;

  @override
  void initState() {
    super.initState();
    final user = ref.read(currentUserProvider);
    _firstNameController = TextEditingController(text: user?.firstName ?? '');
    _lastNameController = TextEditingController(text: user?.lastName ?? '');
    _emailController = TextEditingController(text: user?.email ?? '');
    _phoneController = TextEditingController(text: user?.phone ?? '');
  }

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final ok = await ref.read(profileControllerProvider.notifier).updateProfile(
      email: _emailController.text,
      firstName: _firstNameController.text,
      lastName: _lastNameController.text,
      phone: _phoneController.text.isEmpty ? null : _phoneController.text,
    );

    if (!mounted) return;

    if (ok) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Profil mis à jour.')),
      );
      context.pop();
    } else {
      final error = ref.read(profileControllerProvider).errorMessage ??
          'Mise à jour impossible.';
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(error)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final profileState = ref.watch(profileControllerProvider);

    return AppScaffold(
      title: 'Modifier le profil',
      bottomNavigationBar: const AppBottomNavigation(currentIndex: 4),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            TextFormField(
              controller: _firstNameController,
              validator: (v) => Validators.requiredField(v, label: 'Prénom'),
              decoration: const InputDecoration(labelText: 'Prénom'),
            ),
            const SizedBox(height: AppSizes.md),
            TextFormField(
              controller: _lastNameController,
              validator: (v) => Validators.requiredField(v, label: 'Nom'),
              decoration: const InputDecoration(labelText: 'Nom'),
            ),
            const SizedBox(height: AppSizes.md),
            TextFormField(
              controller: _emailController,
              validator: Validators.email,
              keyboardType: TextInputType.emailAddress,
              decoration: const InputDecoration(labelText: 'Adresse e-mail'),
            ),
            const SizedBox(height: AppSizes.md),
            TextFormField(
              controller: _phoneController,
              keyboardType: TextInputType.phone,
              decoration: const InputDecoration(
                labelText: 'Téléphone (facultatif)',
              ),
            ),
            const SizedBox(height: AppSizes.xl),
            if (profileState.errorMessage != null) ...[
              Text(
                profileState.errorMessage!,
                style: TextStyle(
                  color: Theme.of(context).colorScheme.error,
                ),
              ),
              const SizedBox(height: AppSizes.md),
            ],
            ElevatedButton(
              onPressed: profileState.isLoading ? null : _submit,
              child: Text(
                profileState.isLoading ? 'Enregistrement…' : 'Enregistrer',
              ),
            ),
          ],
        ),
      ),
    );
  }
}
