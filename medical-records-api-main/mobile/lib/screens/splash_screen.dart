import 'package:flutter/material.dart';
import '../api/auth_service.dart';
import 'login_screens/login_screen.dart';
import '../models/app_layout.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _checkLoginStatus();
  }

  Future<void> _checkLoginStatus() async {
    // Đợi một chút để hiển thị splash screen
    await Future.delayed(const Duration(seconds: 2));

    // Kiểm tra trạng thái đăng nhập
    final isLoggedIn = await AuthService.isLoggedIn();

    if (mounted) {
      if (isLoggedIn) {
        // Đã đăng nhập -> chuyển đến home
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (context) => const AppLayout()),
        );
      } else {
        // Chưa đăng nhập -> chuyển đến login
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (context) => const LoginScreen()),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.blue.shade600,
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            // Logo
            Container(
              width: 120,
              height: 120,
              decoration: const BoxDecoration(
                color: Colors.white,
                shape: BoxShape.circle,
              ),
              child: Icon(
                Icons.medical_services,
                size: 60,
                color: Colors.blue.shade600,
              ),
            ),
            const SizedBox(height: 32),

            // App title
            const Text(
              'Medical Records',
              style: TextStyle(
                color: Colors.white,
                fontSize: 28,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Hệ thống quản lý hồ sơ bệnh án',
              style: TextStyle(color: Colors.white70, fontSize: 16),
            ),
            const SizedBox(height: 48),

            // Loading indicator
            const SizedBox(
              width: 24,
              height: 24,
              child: CircularProgressIndicator(
                valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                strokeWidth: 2,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
