import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/constants/app_sizes.dart';
import '../../../core/utils/validators.dart';
import '../../providers/auth_provider.dart';

class RegisterScreen extends ConsumerStatefulWidget {
  const RegisterScreen({super.key});

  @override
  ConsumerState<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends ConsumerState<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _phoneController = TextEditingController();

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _phoneController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final error = await ref.read(authControllerProvider.notifier).signUp(
      email: _emailController.text,
      password: _passwordController.text,
      firstName: _firstNameController.text,
      lastName: _lastNameController.text,
      phone: _phoneController.text.isEmpty ? null : _phoneController.text,
    );

    if (!mounted || error == null) return;

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(error)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authControllerProvider);

    return Scaffold(
      body: SafeArea(
        child: Align(
          alignment: Alignment.topCenter,
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: AppSizes.maxContentWidth),
            child: SingleChildScrollView(
              padding: AppSizes.pagePadding,
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Créer un compte patient',
                      style: Theme.of(context).textTheme.displaySmall,
                    ),
                    const SizedBox(height: AppSizes.xl),
                    Row(
                      children: [
                        Expanded(
                          child: TextFormField(
                            controller: _firstNameController,
                            validator: (v) =>
                                Validators.requiredField(v, label: 'Prénom'),
                            decoration:
                                const InputDecoration(labelText: 'Prénom'),
                          ),
                        ),
                        const SizedBox(width: AppSizes.md),
                        Expanded(
                          child: TextFormField(
                            controller: _lastNameController,
                            validator: (v) =>
                                Validators.requiredField(v, label: 'Nom'),
                            decoration: const InputDecoration(labelText: 'Nom'),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: AppSizes.md),
                    TextFormField(
                      controller: _emailController,
                      validator: Validators.email,
                      keyboardType: TextInputType.emailAddress,
                      decoration:
                          const InputDecoration(labelText: 'Adresse e-mail'),
                    ),
                    const SizedBox(height: AppSizes.md),
                    TextFormField(
                      controller: _passwordController,
                      validator: Validators.password,
                      obscureText: true,
                      decoration:
                          const InputDecoration(labelText: 'Mot de passe'),
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
                    ElevatedButton(
                      onPressed: authState.isLoading ? null : _submit,
                      child: Text(
                        authState.isLoading
                            ? 'Création en cours…'
                            : 'Créer mon compte',
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
