import 'dart:async';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../data/auth_service.dart';
import '../../rfid/data/rfid_service.dart';

enum RfidState { idle, waiting, success, expired, error }

class RfidPage extends StatefulWidget {
  const RfidPage({super.key});

  @override
  State<RfidPage> createState() => _RfidPageState();
}

class _RfidPageState extends State<RfidPage> {
  RfidState _currentState = RfidState.idle;
  Timer? _pollingTimer;
  Timer? _timeoutTimer;
  String _errorMessage = '';

  late final RfidService _rfidService;

  @override
  void initState() {
    super.initState();
    // ✅ Pakai Dio dari AuthService (sudah ada interceptor Bearer token)
    final authService = AuthService();
    _rfidService = RfidService(authService.dio);
    _startPairing();
  }

  Future<void> _startPairing() async {
    if (!mounted) return;

    _pollingTimer?.cancel();
    _timeoutTimer?.cancel();

    setState(() {
      _currentState = RfidState.waiting;
      _errorMessage = '';
    });

    try {
      await _rfidService.startScan();
      if (!mounted) return;
      _startPolling();
      _startTimeout();
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _currentState = RfidState.error;
        _errorMessage = e.toString().replaceFirst('Exception: ', '');
      });
    }
  }

  void _startPolling() {
    _pollingTimer?.cancel();
    _pollingTimer = Timer.periodic(const Duration(seconds: 2), (timer) async {
      if (!mounted) {
        timer.cancel();
        return;
      }

      final status = await _rfidService.checkStatus();
      if (!mounted) return;

      debugPrint('RFID poll status: $status');

      if (status == 'completed') {
        timer.cancel();
        _timeoutTimer?.cancel();
        setState(() => _currentState = RfidState.success);

        Future.delayed(const Duration(seconds: 2), () {
          if (mounted) context.go('/home');
        });
      } else if (status == 'expired' || status == 'failed') {
        timer.cancel();
        _timeoutTimer?.cancel();
        setState(() => _currentState = RfidState.expired);
      }
      // status == 'pending' atau 'error' → lanjut polling
    });
  }

  void _startTimeout() {
    _timeoutTimer?.cancel();
    _timeoutTimer = Timer(const Duration(seconds: 30), () {
      if (!mounted) return;
      if (_currentState == RfidState.waiting) {
        _pollingTimer?.cancel();
        setState(() => _currentState = RfidState.expired);
      }
    });
  }

  @override
  void dispose() {
    _pollingTimer?.cancel();
    _timeoutTimer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        width: double.infinity,
        decoration: const BoxDecoration(
          image: DecorationImage(
            image: AssetImage('assets/images/background-2.jpg'),
            fit: BoxFit.cover,
          ),
        ),
        child: SafeArea(
          child: Column(
            children: [
              Align(
                alignment: Alignment.topLeft,
                child: IconButton(
                  icon: const Icon(Icons.arrow_back, color: Colors.white),
                  onPressed: () => context.pop(),
                ),
              ),
              const Spacer(),
              Container(
                margin: const EdgeInsets.symmetric(horizontal: 24),
                padding: const EdgeInsets.all(32),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.15),
                      blurRadius: 10,
                      offset: const Offset(0, 5),
                    ),
                  ],
                ),
                child: _buildContent(),
              ),
              const Spacer(flex: 2),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildContent() {
    switch (_currentState) {
      case RfidState.waiting:
        return Column(
          children: [
            const CircularProgressIndicator(color: Color(0xFF2D6A4F)),
            const SizedBox(height: 24),
            const Text(
              "Menunggu Kartu...",
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Color(0xFF2D6A4F),
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              "Silakan tap kartu Anda ke sensor mesin RVM sekarang.",
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey),
            ),
          ],
        );

      case RfidState.success:
        return Column(
          children: [
            const Icon(Icons.check_circle, color: Colors.green, size: 64),
            const SizedBox(height: 24),
            const Text(
              "Berhasil!",
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.green,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              "Kartu Anda telah berhasil dihubungkan. Mengalihkan...",
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey),
            ),
          ],
        );

      case RfidState.expired:
        return Column(
          children: [
            const Icon(Icons.timer_off, color: Colors.orange, size: 64),
            const SizedBox(height: 24),
            const Text(
              "Waktu Habis",
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.orange,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              "Anda tidak men-tap kartu dalam batas waktu (30 detik).",
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey),
            ),
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: _startPairing,
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF2D6A4F),
                minimumSize: const Size(double.infinity, 50),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
              child: const Text(
                "Coba Lagi",
                style: TextStyle(color: Colors.white),
              ),
            ),
          ],
        );

      case RfidState.error:
        return Column(
          children: [
            const Icon(Icons.error_outline, color: Colors.red, size: 64),
            const SizedBox(height: 24),
            const Text(
              "Terjadi Kesalahan",
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.red,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              _errorMessage,
              textAlign: TextAlign.center,
              style: const TextStyle(color: Colors.grey),
            ),
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: _startPairing,
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF2D6A4F),
                minimumSize: const Size(double.infinity, 50),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
              child: const Text(
                "Coba Lagi",
                style: TextStyle(color: Colors.white),
              ),
            ),
          ],
        );

      default:
        return const SizedBox();
    }
  }
}