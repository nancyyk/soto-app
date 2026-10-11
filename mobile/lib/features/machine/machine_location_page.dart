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
  static const Color lightGreen = Color(0xFFE7F8EF);

  final MapController _mapController = MapController();
  final ScrollController _listScrollController = ScrollController();

  List<dynamic> _machines = [];
  bool _isLoading = true;
  int _selectedIndex = -1;

  @override
  void initState() {
    super.initState();
    _fetchMachines();
  }

  @override
  void dispose() {
    _mapController.dispose();
    _listScrollController.dispose();
    super.dispose();
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
        // Auto-center to first machine with valid coords after load
        if (_machines.isNotEmpty) {
          _tryAnimateToMachine(0, autoSelect: false);
        }
      }
    } catch (e) {
      debugPrint('Error fetching machines: $e');
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  /// Parse lat/lng from a machine map entry. Returns null if invalid.
  LatLng? _latLngOf(dynamic m) {
    final lat = double.tryParse(m['latitude']?.toString() ?? '');
    final lng = double.tryParse(m['longitude']?.toString() ?? '');
    if (lat == null || lng == null || (lat == 0 && lng == 0)) return null;
    return LatLng(lat, lng);
  }

  void _tryAnimateToMachine(int index, {bool autoSelect = true}) {
    if (index < 0 || index >= _machines.length) return;
    final pos = _latLngOf(_machines[index]);
    if (pos == null) return;

    if (autoSelect) {
      setState(() => _selectedIndex = index);
    }

    // Animate map to the selected machine
    _mapController.move(pos, 15.0);

    // Scroll list to the selected item
    if (autoSelect) {
      const itemHeight = 88.0; // approx card height + margin
      final offset = index * itemHeight;
      _listScrollController.animateTo(
        offset,
        duration: const Duration(milliseconds: 400),
        curve: Curves.easeInOut,
      );
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
                        mapController: _mapController,
                        options: MapOptions(
                          initialCenter: _machines.isNotEmpty && _latLngOf(_machines.first) != null
                              ? _latLngOf(_machines.first)!
                              : const LatLng(-7.2575, 112.7521), // Default Surabaya
                          initialZoom: 14.0,
                        ),
                        children: [
                          TileLayer(
                            urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                            userAgentPackageName: 'com.soto.app',
                          ),
                          MarkerLayer(
                            markers: List.generate(_machines.length, (i) {
                              final m = _machines[i];
                              final pos = _latLngOf(m);
                              if (pos == null) return null;
                              final isSelected = _selectedIndex == i;
                              return Marker(
                                point: pos,
                                width: isSelected ? 54 : 40,
                                height: isSelected ? 54 : 40,
                                child: GestureDetector(
                                  onTap: () => _tryAnimateToMachine(i),
                                  child: Stack(
                                    alignment: Alignment.center,
                                    children: [
                                      Icon(
                                        Icons.location_on,
                                        color: isSelected ? green : Colors.red,
                                        size: isSelected ? 54 : 40,
                                        shadows: isSelected
                                            ? [
                                                Shadow(
                                                  color: green.withOpacity(0.4),
                                                  blurRadius: 8,
                                                )
                                              ]
                                            : null,
                                      ),
                                    ],
                                  ),
                                ),
                              );
                            }).whereType<Marker>().toList(),
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
                      controller: _listScrollController,
                      padding: const EdgeInsets.symmetric(horizontal: 18),
                      itemCount: _machines.length,
                      itemBuilder: (context, index) {
                        final m = _machines[index];
                        final isOnline = m['status_online'] == 1 || m['status_online'] == true;
                        final isSelected = _selectedIndex == index;
                        final hasCoords = _latLngOf(m) != null;
                        return GestureDetector(
                          onTap: hasCoords ? () => _tryAnimateToMachine(index) : null,
                          child: AnimatedContainer(
                            duration: const Duration(milliseconds: 250),
                            margin: const EdgeInsets.only(bottom: 12),
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              border: Border.all(
                                color: isSelected ? green : const Color(0xFFEEEEEE),
                                width: isSelected ? 2.0 : 1.0,
                              ),
                              borderRadius: BorderRadius.circular(16),
                              color: isSelected ? lightGreen.withOpacity(0.5) : Colors.white,
                              boxShadow: isSelected
                                  ? [
                                      BoxShadow(
                                        color: green.withOpacity(0.12),
                                        blurRadius: 8,
                                        offset: const Offset(0, 2),
                                      )
                                    ]
                                  : null,
                            ),
                            child: Row(
                              children: [
                                Container(
                                  width: 48,
                                  height: 48,
                                  decoration: BoxDecoration(
                                    color: isSelected ? lightGreen : const Color(0xFFF6F8F6),
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  child: Icon(Icons.storefront, color: isSelected ? green : const Color(0xFF777777)),
                                ),
                                const SizedBox(width: 14),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        m['nama_lokasi'] ?? 'Mesin SOTO',
                                        style: TextStyle(
                                          fontWeight: FontWeight.w700,
                                          fontSize: 14,
                                          color: isSelected ? green : const Color(0xFF222222),
                                        ),
                                       ),
                                      const SizedBox(height: 6),
                                      Builder(builder: (_) {
                                        final current = (m['kapasitas_terkini'] as num?)?.toInt() ?? 0;
                                        final threshold = (m['threshold_capacity'] as num?)?.toInt() ?? 100;
                                        final maxVal = threshold > 0 ? threshold : 100;
                                        final pct = (current / maxVal).clamp(0.0, 1.0);
                                        final pctInt = (pct * 100).round();
                                        final Color barColor = pct >= 0.9
                                            ? Colors.red
                                            : pct >= 0.6
                                                ? Colors.orange
                                                : green;
                                        return Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Row(
                                              children: [
                                                Text(
                                                  'Kapasitas: ',
                                                  style: const TextStyle(color: Color(0xFF777777), fontSize: 11),
                                                ),
                                                Text(
                                                  '$pctInt%',
                                                  style: TextStyle(
                                                    color: barColor,
                                                    fontSize: 11,
                                                    fontWeight: FontWeight.w700,
                                                  ),
                                                ),
                                                Text(
                                                  ' ($current/$maxVal)',
                                                  style: const TextStyle(color: Color(0xFFAAAAAA), fontSize: 10),
                                                ),
                                              ],
                                            ),
                                            const SizedBox(height: 4),
                                            ClipRRect(
                                              borderRadius: BorderRadius.circular(4),
                                              child: LinearProgressIndicator(
                                                value: pct,
                                                minHeight: 4,
                                                backgroundColor: const Color(0xFFEEEEEE),
                                                valueColor: AlwaysStoppedAnimation<Color>(barColor),
                                              ),
                                            ),
                                          ],
                                        );
                                      }),
                                      if (!hasCoords)
                                        const Padding(
                                          padding: EdgeInsets.only(top: 2),
                                          child: Text(
                                            'Koordinat belum tersedia',
                                            style: TextStyle(color: Colors.orange, fontSize: 10),
                                          ),
                                        ),
                                    ],
                                  ),
                                ),
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.end,
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                      decoration: BoxDecoration(
                                        color: isOnline ? const Color(0xFFE7F8EF) : Colors.red.shade50,
                                        borderRadius: BorderRadius.circular(20),
                                      ),
                                      child: Text(
                                        isOnline ? 'Online' : 'Offline',
                                        style: TextStyle(
                                          color: isOnline ? green : Colors.red,
                                          fontSize: 10,
                                          fontWeight: FontWeight.w600,
                                        ),
                                      ),
                                    ),
                                    if (hasCoords) ...[
                                      const SizedBox(height: 6),
                                      Icon(
                                        Icons.my_location_rounded,
                                        size: 14,
                                        color: isSelected ? green : const Color(0xFFBBBBBB),
                                      ),
                                    ],
                                  ],
                                ),
                              ],
                            ),
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