import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../core/router/app_router.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/app_text_field.dart';

class LoginPage extends StatefulWidget {
  const LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final TextEditingController emailController = TextEditingController();
  final TextEditingController passwordController = TextEditingController();

  @override
  void dispose() {
    emailController.dispose();
    passwordController.dispose();
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

            // Area form
            SafeArea(
              child: Center(
                child: ConstrainedBox(
                  constraints: const BoxConstraints(
                    maxWidth: 420,
                  ),
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 34,
                      vertical: 24,
                    ),
                    child: Column(
                      children: [
                        // Jarak dari bagian atas
                        const SizedBox(height: 80),

                        // Judul
                        const Text(
                          'Masuk',
                          style: TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.w800,
                            color: Color(0xFF111111),
                          ),
                        ),

                        const SizedBox(height: 62),

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

                        const SizedBox(height: 14),

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

                        const SizedBox(height: 10),

                        // Daftar
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Text(
                              'Belum punya akun?',
                              style: TextStyle(
                                fontSize: 12,
                                color: Color(0xFF111111),
                              ),
                            ),

                            TextButton(
                              onPressed: () {
                                context.go(AppRouter.register);
                              },
                              style: TextButton.styleFrom(
                                padding: const EdgeInsets.only(left: 4),
                                minimumSize: Size.zero,
                                tapTargetSize:
                                    MaterialTapTargetSize.shrinkWrap,
                              ),
                              child: const Text(
                                'Daftar',
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

                        // Login Button
                        SizedBox(
                          width: double.infinity,
                          height: 44,
                          child: AppButton(
                            text: 'Login',
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