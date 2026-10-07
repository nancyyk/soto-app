import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../data/reward_data.dart';
import '../data/reward_service.dart';

class RewardDetailPage extends StatelessWidget {
  final RewardData reward;

  const RewardDetailPage({super.key, required this.reward});

  static const Color green = Color(0xFF2D6A4F);
  static const Color lightGreen = Color(0xFFEAF5EF);
  static const Color textDark = Color(0xFF263238);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF9FAF9),
      body: SafeArea(
        child: Column(
          children: [
            // HEADER
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 24, 20, 10),
              child: SizedBox(
                height: 40,
                child: Stack(
                  alignment: Alignment.center,
                  children: [
                    Align(
                      alignment: Alignment.centerLeft,
                      child: InkWell(
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
                    ),
                    const Text(
                      'Detail Reward',
                      style: TextStyle(
                        fontSize: 19,
                        fontWeight: FontWeight.w700,
                        color: textDark,
                      ),
                    ),
                  ],
                ),
              ),
            ),

            // CONTENT
            Expanded(
              child: SingleChildScrollView(
                physics: const BouncingScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(20, 22, 20, 30),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // REWARD IMAGE
                    Container(
                      width: double.infinity,
                      height: 220,
                      decoration: BoxDecoration(
                        color: lightGreen,
                        borderRadius: BorderRadius.circular(24),
                      ),
                      clipBehavior: Clip.antiAlias,
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: Image.asset(
                          reward.imagePath,
                          fit: BoxFit.contain,
                          filterQuality: FilterQuality.high,
                          errorBuilder: (context, error, stackTrace) {
                            return const Center(
                              child: Text(
                                'Reward',
                                style: TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.w700,
                                  color: green,
                                ),
                              ),
                            );
                          },
                        ),
                      ),
                    ),

                    const SizedBox(height: 22),

                    // CATEGORY
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 6,
                      ),
                      decoration: BoxDecoration(
                        color: lightGreen,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        reward.category,
                        style: const TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          color: green,
                        ),
                      ),
                    ),

                    const SizedBox(height: 12),

                    // TITLE
                    Text(
                      reward.title,
                      style: const TextStyle(
                        fontSize: 23,
                        fontWeight: FontWeight.w800,
                        color: textDark,
                      ),
                    ),

                    const SizedBox(height: 12),

                    // POINT
                    Text(
                      '${reward.points} Poin',
                      style: const TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w700,
                        color: green,
                      ),
                    ),

                    const SizedBox(height: 24),

                    // DESCRIPTION
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(18),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(18),
                        border: Border.all(color: const Color(0xFFE6E9E7)),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Deskripsi Reward',
                            style: TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                              color: textDark,
                            ),
                          ),
                          const SizedBox(height: 10),
                          Text(
                            reward.description,
                            style: const TextStyle(
                              fontSize: 13,
                              height: 1.7,
                              color: Color(0xFF707070),
                            ),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 16),

                    // CARA PENUKARAN
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(18),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(18),
                        border: Border.all(color: const Color(0xFFE6E9E7)),
                      ),
                      child: const Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Cara Penukaran',
                            style: TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                              color: textDark,
                            ),
                          ),
                          SizedBox(height: 12),
                          _Step(
                            number: '1',
                            text: 'Pastikan poin kamu mencukupi.',
                          ),
                          SizedBox(height: 10),
                          _Step(
                            number: '2',
                            text: 'Tekan tombol Tukar Sekarang.',
                          ),
                          SizedBox(height: 10),
                          _Step(
                            number: '3',
                            text: 'Reward akan masuk ke akun kamu.',
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 24),

                    // BUTTON
                    SizedBox(
                      width: double.infinity,
                      height: 54,
                      child: ElevatedButton(
                        onPressed: () => _showRedeemDialog(context),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: green,
                          foregroundColor: Colors.white,
                          elevation: 0,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14),
                          ),
                        ),
                        child: const Text(
                          'Tukar Sekarang',
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

  void _showRedeemDialog(BuildContext pageContext) {
    showDialog<void>(
      context: pageContext,
      useSafeArea: true,
      builder: (dialogContext) => _RedeemDialog(
        reward: reward,
        pageContext: pageContext,
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Internal StatefulWidget for the redeem dialog.
// State (isSubmitting, inputError, inputController) is owned by the widget
// and follows the proper State lifecycle — no closure-based state re-declaration.
// ---------------------------------------------------------------------------
class _RedeemDialog extends StatefulWidget {
  final RewardData reward;
  final BuildContext pageContext;

  const _RedeemDialog({required this.reward, required this.pageContext});

  @override
  State<_RedeemDialog> createState() => _RedeemDialogState();
}

class _RedeemDialogState extends State<_RedeemDialog> {
  static const Color green = Color(0xFF2D6A4F);
  static const Color lightGreen = Color(0xFFEAF5EF);
  static const Color textDark = Color(0xFF263238);

  late final bool isMerchandise;
  late final bool isEWallet;
  late final bool isPulsa;
  late final bool isVoucher;
  late final bool requiresInput;

  TextEditingController? _inputController;
  bool _isSubmitting = false;
  String? _inputError;

  @override
  void initState() {
    super.initState();
    final category = widget.reward.category;
    isMerchandise = category == 'Merchandise';
    isEWallet = category == 'E-Wallet';
    isPulsa = category == 'Pulsa';
    isVoucher = category == 'Voucher';
    requiresInput = isMerchandise || isEWallet || isPulsa;
    if (requiresInput) {
      _inputController = TextEditingController();
    }
  }

  @override
  void dispose() {
    _inputController?.dispose();
    super.dispose();
  }

  String get _hintText {
    if (isMerchandise) return 'Masukkan alamat pengiriman lengkap...';
    if (isEWallet) return 'Masukkan nomor E-Wallet...';
    if (isPulsa) return 'Masukkan nomor HP tujuan...';
    return '';
  }

  String get _labelText {
    if (isMerchandise) return 'Alamat Pengiriman';
    if (isEWallet) return 'Nomor E-Wallet';
    if (isPulsa) return 'Nomor HP Tujuan';
    return '';
  }

  String get _helperText {
    if (isMerchandise) return 'Pastikan alamat yang dimasukkan sudah benar.';
    if (isEWallet) return 'Pastikan nomor E-Wallet sudah benar.';
    if (isPulsa) return 'Pastikan nomor HP tujuan sudah benar.';
    return '';
  }

  String? _validate() {
    if (requiresInput && (_inputController?.text.trim().isEmpty ?? true)) {
      if (isMerchandise) return 'Alamat pengiriman wajib diisi.';
      if (isEWallet) return 'Nomor E-Wallet wajib diisi.';
      if (isPulsa) return 'Nomor HP tujuan wajib diisi.';
    }
    return null;
  }

  Future<void> _submit() async {
    final validationError = _validate();
    if (validationError != null) {
      setState(() => _inputError = validationError);
      return;
    }

    setState(() => _isSubmitting = true);

    try {
      final textVal = _inputController?.text.trim();
      await RewardService().redeemReward(
        rewardId: widget.reward.id,
        shippingAddress: isMerchandise ? textVal : null,
        recipientNumber: (isEWallet || isPulsa) ? textVal : null,
      );

      if (!mounted) return;
      // Close dialog first, then navigate away
      Navigator.of(context).pop();

      final pageCtx = widget.pageContext;
      if (pageCtx.mounted) {
        ScaffoldMessenger.of(pageCtx).showSnackBar(
          const SnackBar(content: Text('Reward berhasil ditukar! Cek riwayat Anda.')),
        );
        GoRouter.of(pageCtx).pop();
      }
    } catch (e) {
      if (!mounted) return;
      setState(() => _isSubmitting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceAll('Exception: ', ''))),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final reward = widget.reward;
    final media = MediaQuery.of(context);

    return Dialog(
      insetPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 20),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(22)),
      clipBehavior: Clip.antiAlias,
      child: ConstrainedBox(
        constraints: BoxConstraints(
          maxWidth: 440,
          maxHeight: (media.size.height - media.viewInsets.bottom) * 0.85,
        ),
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(22),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Konfirmasi Penukaran',
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: textDark),
              ),
              const SizedBox(height: 18),

              // Reward info card
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFFF9FAF9),
                  border: Border.all(color: const Color(0xFFE6E9E7)),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Row(
                  children: [
                    Container(
                      width: 76,
                      height: 76,
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(color: lightGreen, borderRadius: BorderRadius.circular(12)),
                      child: Image.asset(
                        reward.imagePath,
                        fit: BoxFit.contain,
                        errorBuilder: (_, __, ___) => const Icon(Icons.card_giftcard, color: green),
                      ),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(reward.title, maxLines: 2, overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontSize: 15, height: 1.3, fontWeight: FontWeight.w700, color: textDark)),
                          const SizedBox(height: 6),
                          Text(reward.category, style: const TextStyle(fontSize: 12, color: Color(0xFF707070))),
                          const SizedBox(height: 6),
                          Text('${reward.points} Poin', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: green)),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              // DATA PENERIMA section
              if (requiresInput) ...[
                const SizedBox(height: 20),
                const Text('Data Penerima', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: textDark)),
                const SizedBox(height: 10),
                Text(_labelText, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: textDark)),
                const SizedBox(height: 6),
                TextField(
                  controller: _inputController,
                  minLines: isMerchandise ? 3 : 1,
                  maxLines: isMerchandise ? 5 : 1,
                  keyboardType: isMerchandise ? TextInputType.multiline : TextInputType.phone,
                  textCapitalization: isMerchandise ? TextCapitalization.sentences : TextCapitalization.none,
                  onChanged: (_) {
                    if (_inputError != null) setState(() => _inputError = null);
                  },
                  decoration: InputDecoration(
                    hintText: _hintText,
                    hintStyle: const TextStyle(fontSize: 13, color: Color(0xFF9AA19D)),
                    errorText: _inputError,
                    contentPadding: isMerchandise
                        ? const EdgeInsets.all(14)
                        : const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    filled: true,
                    fillColor: Colors.white,
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: Color(0xFFE0E5E2))),
                    enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: Color(0xFFE0E5E2))),
                    focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: green, width: 1.4)),
                    errorBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: Colors.red)),
                  ),
                ),
                const SizedBox(height: 7),
                Text(_helperText, style: const TextStyle(fontSize: 11, color: Color(0xFF707070))),
              ] else if (isVoucher) ...[
                const SizedBox(height: 20),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: lightGreen,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: const Color(0xFFCCE8D9)),
                  ),
                  child: const Row(
                    children: [
                      Icon(Icons.info_outline, color: green, size: 20),
                      SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          'Voucher akan tersedia sebagai kode setelah penukaran berhasil.',
                          style: TextStyle(fontSize: 12, height: 1.4, color: Color(0xFF1B4332), fontWeight: FontWeight.w500),
                        ),
                      ),
                    ],
                  ),
                ),
              ],

              const SizedBox(height: 22),
              Row(
                children: [
                  Expanded(
                    child: SizedBox(
                      height: 48,
                      child: OutlinedButton(
                        onPressed: _isSubmitting ? null : () => Navigator.of(context).pop(),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: textDark,
                          side: const BorderSide(color: Color(0xFFDDE3DF)),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        ),
                        child: const Text('Batal', style: TextStyle(fontWeight: FontWeight.w700)),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    flex: 2,
                    child: SizedBox(
                      height: 48,
                      child: ElevatedButton(
                        onPressed: _isSubmitting ? null : _submit,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: green,
                          foregroundColor: Colors.white,
                          elevation: 0,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        ),
                        child: _isSubmitting
                            ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                            : const Text('Tukar Sekarang', style: TextStyle(fontWeight: FontWeight.w700)),
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

class _Step extends StatelessWidget {
  final String number;
  final String text;

  const _Step({required this.number, required this.text});

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 24,
          height: 24,
          alignment: Alignment.center,
          decoration: const BoxDecoration(
            color: Color(0xFFEAF5EF),
            shape: BoxShape.circle,
          ),
          child: Text(
            number,
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              color: Color(0xFF2D6A4F),
            ),
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Text(
            text,
            style: const TextStyle(
              fontSize: 13,
              height: 1.4,
              color: Color(0xFF666666),
            ),
          ),
        ),
      ],
    );
  }
}
