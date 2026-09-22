import 'dart:async';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:dio/dio.dart';
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
  String _errorMessage = '';

  late final RfidService _rfidService;

  @override
  void initState() {
    super.initState();
    // Inisialisasi dio. (Ubah base url sesuaikan dengan host Anda)
    // Di sistem nyata, lebih baik injeksi authService.dio yang sudah terpasang interceptor Bearer token
    final dio = Dio(BaseOptions(baseUrl: 'http://10.0.2.2:8000'));
    _rfidService = RfidService(dio); 
    
    _startPairing();
  }

  void _startPairing() async {
    setState(() {
      _currentState = RfidState.waiting;
      _errorMessage = '';
    });

    try {
      await _rfidService.startScan();
      _startPolling();
    } catch (e) {
      setState(() {
        _currentState = RfidState.error;
        _errorMessage = e.toString();
      });
    }
  }

  void _startPolling() {
    _pollingTimer?.cancel();
    _pollingTimer = Timer.periodic(const Duration(seconds: 2), (timer) async {
      final status = await _rfidService.checkStatus();

      if (status == 'completed') {
        timer.cancel();
        setState(() => _currentState = RfidState.success);
        
        Future.delayed(const Duration(seconds: 2), () {
          if (mounted) context.go('/home');
        });
      } else if (status == 'expired') {
        timer.cancel();
        setState(() => _currentState = RfidState.expired);
      } else if (status == 'error') {
        // Abaikan sementara jika timeout jaringan
      }
    });
  }

  @override
  void dispose() {
    _pollingTimer?.cancel();
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
                      color: Colors.black.withValues(),
                      blurRadius: 10,
                      offset: const Offset(0, 5),
                    )
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
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFF2D6A4F)),
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
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.green),
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
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.orange),
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
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              child: const Text("Coba Lagi", style: TextStyle(color: Colors.white)),
            )
          ],
        );

      case RfidState.error:
        return Column(
          children: [
            const Icon(Icons.error_outline, color: Colors.red, size: 64),
            const SizedBox(height: 24),
            const Text(
              "Terjadi Kesalahan",
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.red),
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
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              child: const Text("Coba Lagi", style: TextStyle(color: Colors.white)),
            )
          ],
        );

      default:
        return const SizedBox();
    }
  }
}