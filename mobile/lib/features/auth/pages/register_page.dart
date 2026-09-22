import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../core/router/app_router.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/app_text_field.dart';
import '../data/auth_service.dart';

class RegisterPage extends StatefulWidget {
  const RegisterPage({super.key});

  @override
  State<RegisterPage> createState() => _RegisterPageState();
}

class _RegisterPageState extends State<RegisterPage> {
  final TextEditingController fullNameController = TextEditingController();
  final TextEditingController emailController = TextEditingController();
  final TextEditingController passwordController = TextEditingController();
  final TextEditingController confirmPasswordController =
      TextEditingController();
  final AuthService _authService = AuthService();
  bool _isLoading = false;

  @override
  void dispose() {
    fullNameController.dispose();
    emailController.dispose();
    passwordController.dispose();
    confirmPasswordController.dispose();
    super.dispose();
  }

  Future<void> _handleRegister() async {
    final name = fullNameController.text.trim();
    final email = emailController.text.trim();
    final pass = passwordController.text;
    final confirm = confirmPasswordController.text;

    if (name.isEmpty || email.isEmpty || pass.isEmpty || confirm.isEmpty) {
      _showSnack('Semua field harus diisi');
      return;
    }

    if (pass.length < 8) {
      _showSnack('Password minimal 8 karakter');
      return;
    }

    if (pass != confirm) {
      _showSnack('Password tidak cocok');
      return;
    }

    FocusScope.of(context).unfocus();
    setState(() => _isLoading = true);

    try {
      await _authService.register(
        name: name,
        email: email,
        password: pass,
      );

      if (mounted) {
        context.go(AppRouter.rfid);
      }
    } catch (e) {
      if (mounted) {
        _showSnack(e.toString().replaceFirst('Exception: ', ''));
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _showSnack(String message) {
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SizedBox.expand(
        child: Stack(
          fit: StackFit.expand,
          children: [
            Image.asset(
              'assets/images/background-2.jpg',
              fit: BoxFit.cover,
            ),
            SafeArea(
              child: Center(
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 420),
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 34,
                      vertical: 20,
                    ),
                    child: Column(
                      children: [
                        const SizedBox(height: 18),
                        const Text(
                          'Daftar',
                          style: TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.w800,
                            color: Color(0xFF111111),
                          ),
                        ),
                        const SizedBox(height: 22),

                        // Nama Lengkap
                        const Align(
                          alignment: Alignment.centerLeft,
                          child: Text(
                            'Nama Lengkap',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w500,
                              color: Color(0xFF111111),
                            ),
                          ),
                        ),
                        const SizedBox(height: 6),
                        AppTextField(
                          controller: fullNameController,
                          hint: 'Nama lengkap',
                        ),

                        const SizedBox(height: 12),

                        // Email
                        const Align(
                          alignment: Alignment.centerLeft,
                          child: Text(
                            'Email',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w500,
                              color: Color(0xFF111111),
                            ),
                          ),
                        ),
                        const SizedBox(height: 6),
                        AppTextField(
                          controller: emailController,
                          hint: 'Email',
                          keyboardType: TextInputType.emailAddress,
                        ),

                        const SizedBox(height: 12),

                        // Password
                        const Align(
                          alignment: Alignment.centerLeft,
                          child: Text(
                            'Password',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w500,
                              color: Color(0xFF111111),
                            ),
                          ),
                        ),
                        const SizedBox(height: 6),
                        AppTextField(
                          controller: passwordController,
                          hint: 'Password (min. 8 karakter)',
                          obscureText: true,
                        ),

                        const SizedBox(height: 12),

                        // Konfirmasi Password
                        const Align(
                          alignment: Alignment.centerLeft,
                          child: Text(
                            'Konfirmasi Password',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w500,
                              color: Color(0xFF111111),
                            ),
                          ),
                        ),
                        const SizedBox(height: 6),
                        AppTextField(
                          controller: confirmPasswordController,
                          hint: 'Konfirmasi password',
                          obscureText: true,
                        ),

                        const SizedBox(height: 10),

                        // Link ke Login
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Text(
                              'Sudah punya akun?',
                              style: TextStyle(
                                fontSize: 12,
                                color: Color(0xFF111111),
                              ),
                            ),
                            TextButton(
                              onPressed: _isLoading
                                  ? null
                                  : () => context.go(AppRouter.login),
                              style: TextButton.styleFrom(
                                padding: const EdgeInsets.only(left: 4),
                                minimumSize: Size.zero,
                                tapTargetSize:
                                    MaterialTapTargetSize.shrinkWrap,
                              ),
                              child: const Text(
                                'Masuk',
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                  color: Color(0xFF2D6A4F),
                                ),
                              ),
                            ),
                          ],
                        ),

                        const SizedBox(height: 10),

                        // Tombol Register
                        SizedBox(
                          width: double.infinity,
                          height: 44,
                          child: AppButton(
                            text: _isLoading ? 'Loading...' : 'Register',
                            onPressed:
                                _isLoading ? () {} : _handleRegister,
                            backgroundColor: const Color(0xFF2D6A4F),
                            textColor: Colors.white,
                            height: 44,
                          ),
                        ),

                        const SizedBox(height: 20),
                      ],
                    ),
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