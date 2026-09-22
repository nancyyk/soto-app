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
    final authService = AuthService();
    _rfidService = RfidService(authService.dio);
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

  String get _statusText {
    switch (_currentState) {
      case RfidState.idle:
        return 'Belum Terhubung';
      case RfidState.waiting:
        return 'Menunggu Kartu...';
      case RfidState.success:
        return 'Berhasil Terhubung';
      case RfidState.expired:
        return 'Waktu Habis';
      case RfidState.error:
        return 'Gagal';
    }
  }

  Color get _statusColor {
    switch (_currentState) {
      case RfidState.idle:
        return const Color(0xFFE8873A);
      case RfidState.waiting:
        return const Color(0xFF2D6A4F);
      case RfidState.success:
        return Colors.green;
      case RfidState.expired:
      case RfidState.error:
        return Colors.red;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: const BoxDecoration(
          image: DecorationImage(
            image: AssetImage('assets/images/background-2.jpg'),
            fit: BoxFit.cover,
          ),
        ),
        child: SafeArea(
          child: SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Tombol back
                Align(
                  alignment: Alignment.topLeft,
                  child: IconButton(
                    icon: const Icon(
                      Icons.arrow_back,
                      color: Colors.white,
                    ),
                    onPressed: () => context.pop(),
                  ),
                ),

                const SizedBox(height: 60),

                // Gambar RFID
                Image.asset(
                  'assets/images/RFIDcard.png',
                  height: 200,
                  fit: BoxFit.contain,
                ),

                const SizedBox(height: 40),

                // Judul
                const Text(
                  'Hubungkan Kartu RFID',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF111111),
                  ),
                ),

                const SizedBox(height: 12),

                // Deskripsi
                const Text(
                  'Tempelkan kartu RFID kamu ke mesin untuk mulai menabung botol',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 14,
                    color: Color(0xFF333333),
                    height: 1.4,
                  ),
                ),

                const SizedBox(height: 40),

                // Card Status
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 20,
                    vertical: 16,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(14),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.05),
                        blurRadius: 6,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          const Text(
                            'Status',
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w600,
                              color: Color(0xFF111111),
                            ),
                          ),
                          const Spacer(),
                          Text(
                            _statusText,
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w600,
                              color: _statusColor,
                            ),
                          ),
                        ],
                      ),
                      if (_currentState == RfidState.error &&
                          _errorMessage.isNotEmpty) ...[
                        const SizedBox(height: 8),
                        Text(
                          _errorMessage,
                          style: const TextStyle(
                            fontSize: 12,
                            color: Colors.red,
                          ),
                        ),
                      ],
                    ],
                  ),
                ),

                const SizedBox(height: 24),

                // Tombol Hubungkan RFID
                SizedBox(
                  width: double.infinity,
                  height: 52,
                  child: ElevatedButton(
                    onPressed: _currentState == RfidState.waiting
                        ? null
                        : _startPairing,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF2D6A4F),
                      disabledBackgroundColor: const Color(0xFF8FB3A3),
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      elevation: 0,
                    ),
                    child: _currentState == RfidState.waiting
                        ? const SizedBox(
                            width: 22,
                            height: 22,
                            child: CircularProgressIndicator(
                              strokeWidth: 2.5,
                              color: Colors.white,
                            ),
                          )
                        : const Text(
                            'Hubungkan RFID',
                            style: TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                  ),
                ),

                const SizedBox(height: 8),

                // Link Petunjuk Penggunaan
                Center(
                  child: TextButton(
                    onPressed: () {
                      // TODO: arahkan ke halaman petunjuk
                      // context.push('/petunjuk');
                    },
                    child: const Text(
                      'Petunjuk Penggunaan',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF3B82F6),
                      ),
                    ),
                  ),
                ),

                const SizedBox(height: 40),
              ],
            ),
          ),
        ),
      ),
    );
  }
}