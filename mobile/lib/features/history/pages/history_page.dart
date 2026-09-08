import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../core/router/app_router.dart';
import '../../../core/widgets/bottom_nav_bar.dart';
import 'transaction_detail_page.dart';

class HistoryPage extends StatefulWidget {
  const HistoryPage({super.key});

  @override
  State<HistoryPage> createState() => _HistoryPageState();
}

class _HistoryPageState extends State<HistoryPage> {
  final TextEditingController _searchController = TextEditingController();

  final List<TransactionData> _transactions = const [
    TransactionData(
      id: 'TXN-010985-001',
      date: '23 Mei 2026',
      time: '10.20 WIB',
      machine: 'Mesin SOTO #21',
      points: 24,
      bottles: 12,
      status: 'Berhasil',
    ),
    TransactionData(
      id: 'TXN-010982-004',
      date: '20 Mei 2026',
      time: '14.35 WIB',
      machine: 'Mesin SOTO #15',
      points: 16,
      bottles: 11,
      status: 'Berhasil',
    ),
    TransactionData(
      id: 'TXN-010974-002',
      date: '10 Mei 2026',
      time: '09.15 WIB',
      machine: 'Mesin SOTO #08',
      points: 30,
      bottles: 15,
      status: 'Berhasil',
    ),
    TransactionData(
      id: 'TXN-010968-003',
      date: '7 Mei 2026',
      time: '16.40 WIB',
      machine: 'Mesin SOTO #21',
      points: 24,
      bottles: 12,
      status: 'Berhasil',
    ),
  ];

  String _search = '';

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  List<TransactionData> get _filteredTransactions {
    if (_search.trim().isEmpty) {
      return _transactions;
    }

    final query = _search.toLowerCase();

    return _transactions.where((transaction) {
      return transaction.machine.toLowerCase().contains(query) ||
          transaction.date.toLowerCase().contains(query) ||
          transaction.id.toLowerCase().contains(query);
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF9FAF9),
      bottomNavigationBar: const BottomNavBar(),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 28, 20, 12),
              child: Row(
                children: [
                  InkWell(
                    borderRadius: BorderRadius.circular(24),
                    onTap: () {
                      if (context.canPop()) {
                        context.pop();
                      } else {
                        context.go(AppRouter.home);
                      }
                    },
                    child: const Padding(
                      padding: EdgeInsets.all(8),
                      child: Icon(
                        Icons.arrow_back_ios_new,
                        size: 20,
                        color: Color(0xFF263238),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  const Text(
                    'Riwayat Transaksi',
                    style: TextStyle(
                      fontSize: 21,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFF263238),
                    ),
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 14, 20, 12),
              child: Row(
                children: [
                  Expanded(
                    child: Container(
                      height: 46,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(13),
                        border: Border.all(color: const Color(0xFFE2E5E3)),
                      ),
                      child: TextField(
                        controller: _searchController,
                        onChanged: (value) {
                          setState(() {
                            _search = value;
                          });
                        },
                        decoration: const InputDecoration(
                          border: InputBorder.none,
                          hintText: 'Cari Transaksi',
                          hintStyle: TextStyle(
                            color: Color(0xFFAAAAAA),
                            fontSize: 13,
                          ),
                          prefixIcon: Icon(
                            Icons.search,
                            size: 20,
                            color: Color(0xFF8D8D8D),
                          ),
                          contentPadding: EdgeInsets.symmetric(vertical: 13),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Container(
                    height: 46,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(13),
                      border: Border.all(color: const Color(0xFFE2E5E3)),
                    ),
                    child: IconButton(
                      onPressed: () {},
                      icon: const Icon(
                        Icons.tune,
                        size: 20,
                        color: Color(0xFF2D6A4F),
                      ),
                    ),
                  ),
                ],
              ),
            ),
            Expanded(
              child: _filteredTransactions.isEmpty
                  ? const Center(
                      child: Text(
                        'Transaksi tidak ditemukan',
                        style: TextStyle(
                          fontSize: 14,
                          color: Color(0xFF888888),
                        ),
                      ),
                    )
                  : ListView.separated(
                      physics: const BouncingScrollPhysics(),
                      padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
                      itemCount: _filteredTransactions.length,
                      separatorBuilder: (context, index) =>
                          const SizedBox(height: 12),
                      itemBuilder: (context, index) {
                        final transaction = _filteredTransactions[index];

                        return _TransactionCard(
                          transaction: transaction,
                          onTap: () {
                            context.push(
                              AppRouter.transactionDetail,
                              extra: transaction,
                            );
                          },
                        );
                      },
                    ),
            ),
          ],
        ),
      ),
    );
  }
}

class _TransactionCard extends StatelessWidget {
  final TransactionData transaction;
  final VoidCallback onTap;

  const _TransactionCard({required this.transaction, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(17),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(17),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(17),
            border: Border.all(color: const Color(0xFFE7EAE8)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 42,
                    height: 42,
                    decoration: BoxDecoration(
                      color: const Color(0xFFEAF5EF),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(
                      Icons.recycling,
                      color: Color(0xFF2D6A4F),
                      size: 23,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          transaction.date,
                          style: const TextStyle(
                            fontSize: 12,
                            color: Color(0xFF888888),
                          ),
                        ),
                        const SizedBox(height: 3),
                        Text(
                          transaction.machine,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w700,
                            color: Color(0xFF263238),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    '+${transaction.points} Poin',
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFF2D6A4F),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 14),
              const Divider(height: 1, color: Color(0xFFEDEDED)),
              const SizedBox(height: 12),
              Row(
                children: [
                  const Icon(
                    Icons.local_drink_outlined,
                    size: 17,
                    color: Color(0xFF8A8A8A),
                  ),
                  const SizedBox(width: 6),
                  Text(
                    '${transaction.bottles} Botol',
                    style: const TextStyle(
                      fontSize: 12,
                      color: Color(0xFF666666),
                    ),
                  ),
                  const SizedBox(width: 16),
                  const Icon(
                    Icons.access_time,
                    size: 16,
                    color: Color(0xFF8A8A8A),
                  ),
                  const SizedBox(width: 6),
                  Text(
                    transaction.time,
                    style: const TextStyle(
                      fontSize: 12,
                      color: Color(0xFF666666),
                    ),
                  ),
                  const Spacer(),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 9,
                      vertical: 5,
                    ),
                    decoration: BoxDecoration(
                      color: const Color(0xFFEAF5EF),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(
                      transaction.status,
                      style: const TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF2D6A4F),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
