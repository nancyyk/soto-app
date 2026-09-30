import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import '../auth/data/auth_service.dart';
import '../../core/widgets/bottom_nav_bar.dart';

class MachineLocationPage extends StatefulWidget {
  const MachineLocationPage({super.key});

  @override
  State<MachineLocationPage> createState() => _MachineLocationPageState();
}

class _MachineLocationPageState extends State<MachineLocationPage> {
  static const Color green = Color(0xFF2D6A4F);
  List<dynamic> _machines = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchMachines();
  }

  Future<void> _fetchMachines() async {
    try {
      final dio = AuthService().dio;
      final response = await dio.get('/machines');
      if (mounted) {
        setState(() {
          _machines = response.data as List;
          _isLoading = false;
        });
      }
    } catch (e) {
      debugPrint('Error fetching machines: $e');
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        centerTitle: true,
        leading: IconButton(
          onPressed: () => context.pop(),
          icon: const Icon(Icons.arrow_back_ios_new, color: Colors.black, size: 20),
        ),
        title: const Text(
          'Lokasi Mesin',
          style: TextStyle(color: Colors.black, fontSize: 16, fontWeight: FontWeight.w700),
        ),
      ),
      body: SafeArea(
        child: Column(
          children: [
            // PETA (Interactive flutter_map)
            Padding(
              padding: const EdgeInsets.fromLTRB(18, 22, 18, 0),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(18),
                child: SizedBox(
                  width: double.infinity,
                  height: 295,
                  child: _isLoading 
                    ? const Center(child: CircularProgressIndicator(color: green))
                    : FlutterMap(
                        options: MapOptions(
                          initialCenter: const LatLng(-6.200000, 106.816666), // Default Jakarta
                          initialZoom: 12.0,
                        ),
                        children: [
                          TileLayer(
                            urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                            userAgentPackageName: 'com.soto.app',
                          ),
                          MarkerLayer(
                            markers: _machines.map((m) {
                              final lat = double.tryParse(m['latitude']?.toString() ?? '0') ?? 0;
                              final lng = double.tryParse(m['longitude']?.toString() ?? '0') ?? 0;
                              return Marker(
                                point: LatLng(lat, lng),
                                width: 40,
                                height: 40,
                                child: const Icon(Icons.location_on, color: Colors.red, size: 40),
                              );
                            }).toList(),
                          ),
                        ],
                      ),
                ),
              ),
            ),
            const SizedBox(height: 24),
            // DAFTAR MESIN
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 18),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Daftar Mesin SOTO', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16, color: Color(0xFF222222))),
                  Text('${_machines.length} Mesin', style: const TextStyle(color: green, fontSize: 12, fontWeight: FontWeight.w600)),
                ],
              ),
            ),
            const SizedBox(height: 16),
            Expanded(
              child: _isLoading
                ? const Center(child: CircularProgressIndicator(color: green))
                : _machines.isEmpty
                  ? const Center(child: Text('Belum ada mesin terdaftar', style: TextStyle(color: Colors.grey)))
                  : ListView.builder(
                      padding: const EdgeInsets.symmetric(horizontal: 18),
                      itemCount: _machines.length,
                      itemBuilder: (context, index) {
                        final m = _machines[index];
                        final isOnline = m['status_online'] == 1 || m['status_online'] == true;
                        return Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            border: Border.all(color: const Color(0xFFEEEEEE)),
                            borderRadius: BorderRadius.circular(16),
                            color: Colors.white,
                          ),
                          child: Row(
                            children: [
                              Container(
                                width: 48,
                                height: 48,
                                decoration: BoxDecoration(color: const Color(0xFFF6F8F6), borderRadius: BorderRadius.circular(12)),
                                child: const Icon(Icons.storefront, color: green),
                              ),
                              const SizedBox(width: 14),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(m['nama_lokasi'] ?? 'Mesin SOTO', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14, color: Color(0xFF222222))),
                                    const SizedBox(height: 4),
                                    Text('Kapasitas: ${m['kapasitas_terkini'] ?? 0}/${m['kapasitas_maksimal'] ?? 0}', style: const TextStyle(color: Color(0xFF777777), fontSize: 12)),
                                  ],
                                ),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                decoration: BoxDecoration(
                                  color: isOnline ? const Color(0xFFE7F8EF) : Colors.red.shade50,
                                  borderRadius: BorderRadius.circular(20),
                                ),
                                child: Text(isOnline ? 'Online' : 'Offline', style: TextStyle(color: isOnline ? green : Colors.red, fontSize: 10, fontWeight: FontWeight.w600)),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: const BottomNavBar(),
    );
  }
}