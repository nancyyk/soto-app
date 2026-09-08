import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

class TransactionData {
  final String id;
  final String date;
  final String time;
  final String machine;
  final int points;
  final int bottles;
  final String status;

  const TransactionData({
    required this.id,
    required this.date,
    required this.time,
    required this.machine,
    required this.points,
    required this.bottles,
    required this.status,
  });
}

class TransactionDetailPage extends StatelessWidget {
  final TransactionData transaction;

  const TransactionDetailPage({super.key, required this.transaction});

  static const Color green = Color(0xFF2D6A4F);
  static const Color lightGreen = Color(0xFFEAF5EF);
  static const Color textDark = Color(0xFF263238);
  static const Color textGrey = Color(0xFF757575);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: Column(
          children: [
            // HEADER
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 14, 20, 10),
              child: Row(
                children: [
                  InkWell(
                    borderRadius: BorderRadius.circular(24),
                    onTap: () => context.pop(),
                    child: const Padding(
                      padding: EdgeInsets.all(8),
                      child: Icon(
                        Icons.arrow_back_ios_new,
                        size: 20,
                        color: textDark,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  const Text(
                    'Detail Transaksi',
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w700,
                      color: textDark,
                    ),
                  ),
                ],
              ),
            ),

            // CONTENT
            Expanded(
              child: SingleChildScrollView(
                physics: const BouncingScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(20, 18, 20, 28),
                child: Column(
                  children: [
                    // SUCCESS ICON
                    Container(
                      width: 76,
                      height: 76,
                      decoration: const BoxDecoration(
                        color: lightGreen,
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(
                        Icons.check_circle,
                        size: 52,
                        color: green,
                      ),
                    ),

                    const SizedBox(height: 18),

                    // POINT
                    Text(
                      '+${transaction.points} Poin',
                      style: const TextStyle(
                        fontSize: 28,
                        fontWeight: FontWeight.w800,
                        color: green,
                      ),
                    ),

                    const SizedBox(height: 6),

                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 14,
                        vertical: 6,
                      ),
                      decoration: BoxDecoration(
                        color: lightGreen,
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        transaction.status,
                        style: const TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: green,
                        ),
                      ),
                    ),

                    const SizedBox(height: 30),

                    // DETAIL CARD
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(18),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(18),
                        border: Border.all(color: const Color(0xFFE8E8E8)),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withAlpha(15),
                            blurRadius: 14,
                            offset: const Offset(0, 5),
                          ),
                        ],
                      ),
                      child: Column(
                        children: [
                          _DetailRow(
                            label: 'ID Transaksi',
                            value: transaction.id,
                          ),
                          _divider(),
                          _DetailRow(label: 'Tanggal', value: transaction.date),
                          _divider(),
                          _DetailRow(label: 'Jam', value: transaction.time),
                          _divider(),
                          _DetailRow(
                            label: 'Jumlah Botol',
                            value: '${transaction.bottles} Botol',
                          ),
                          _divider(),
                          _DetailRow(
                            label: 'Poin',
                            value: '${transaction.points} Poin',
                            valueColor: green,
                          ),
                          _divider(),
                          _DetailRow(
                            label: 'Nama Mesin',
                            value: transaction.machine,
                          ),
                          _divider(),
                          _DetailRow(
                            label: 'Status',
                            value: transaction.status,
                            valueColor: green,
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 28),

                    // BUTTON
                    SizedBox(
                      width: double.infinity,
                      height: 52,
                      child: ElevatedButton(
                        onPressed: () => context.pop(),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: green,
                          foregroundColor: Colors.white,
                          elevation: 0,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14),
                          ),
                        ),
                        child: const Text(
                          'Kembali',
                          style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _divider() {
    return const Padding(
      padding: EdgeInsets.symmetric(vertical: 14),
      child: Divider(height: 1, color: Color(0xFFEDEDED)),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  final Color? valueColor;

  const _DetailRow({required this.label, required this.value, this.valueColor});

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(
          flex: 4,
          child: Text(
            label,
            style: const TextStyle(fontSize: 13, color: Color(0xFF8A8A8A)),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          flex: 6,
          child: Text(
            value,
            textAlign: TextAlign.right,
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: valueColor ?? const Color(0xFF263238),
            ),
          ),
        ),
      ],
    );
  }
}
