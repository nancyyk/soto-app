import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../core/router/app_router.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/app_text_field.dart';

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

  @override
  void dispose() {
    fullNameController.dispose();
    emailController.dispose();
    passwordController.dispose();
    confirmPasswordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SizedBox.expand(
        child: Stack(
          fit: StackFit.expand,
          children: [
            // Background sesuai Figma
            Image.asset(
              'assets/images/background-2.jpg',
              fit: BoxFit.cover,
            ),

            // Form Register
            SafeArea(
              child: Center(
                child: ConstrainedBox(
                  constraints: const BoxConstraints(
                    maxWidth: 420,
                  ),
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 34,
                      vertical: 20,
                    ),
                    child: Column(
                      children: [
                        const SizedBox(height: 18),

                        // Judul
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
                          hint: 'Password',
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

                        // Login
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
                              onPressed: () {
                                context.go(AppRouter.login);
                              },
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

                        // Register Button
                        SizedBox(
                          width: double.infinity,
                          height: 44,
                          child: AppButton(
                            text: 'Register',
                            onPressed: () {
                              context.go(AppRouter.rfid);
                            },
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