import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/pages/splash_page.dart';
import '../../features/auth/pages/onboarding_page.dart';
import '../../features/auth/pages/login_page.dart';
import '../../features/auth/pages/register_page.dart';
import '../../features/auth/pages/rfid_page.dart';

import '../../features/home/pages/home_page.dart';

import '../../features/history/pages/history_page.dart';
import '../../features/history/pages/transaction_detail_page.dart';

import '../../features/reward/data/reward_data.dart';
import '../../features/reward/pages/reward_page.dart';
import '../../features/reward/pages/reward_detail_page.dart';

import '../../features/machine/machine_location_page.dart';
import '../../features/notification/notification_page.dart';

import '../../features/profile/pages/profile_page.dart';
import '../../features/profile/pages/edit_profile_page.dart';
import '../../features/profile/pages/change_password_page.dart';
import '../../features/profile/pages/settings_page.dart';
import '../../features/profile/pages/about_page.dart';

class AppRouter {
  AppRouter._();

  // ROUTE PATH

  static const String splash = '/';
  static const String onboarding = '/onboarding';
  static const String login = '/login';
  static const String register = '/register';
  static const String rfid = '/rfid';
  static const String home = '/home';

  static const String history = '/history';
  static const String transactionDetail = '/history/detail';

  static const String reward = '/reward';
  static const String rewardDetail = '/reward/detail';

  static const String machine = '/machine';
  static const String notification = '/notification';

  static const String profile = '/profile';
  static const String editProfile = '/profile/edit';
  static const String changePassword = '/profile/change-password';
  static const String settings = '/profile/settings';
  static const String about = '/about';

  // ROUTER

  static final GoRouter router = GoRouter(
    initialLocation: splash,
    routes: <RouteBase>[
      GoRoute(
        path: splash,
        builder: (BuildContext context, GoRouterState state) {
          return const SplashPage();
        },
      ),
      GoRoute(
        path: onboarding,
        builder: (BuildContext context, GoRouterState state) {
          return const OnboardingPage();
        },
      ),
      GoRoute(
        path: login,
        builder: (BuildContext context, GoRouterState state) {
          return const LoginPage();
        },
      ),
      GoRoute(
        path: register,
        builder: (BuildContext context, GoRouterState state) {
          return const RegisterPage();
        },
      ),
      GoRoute(
        path: rfid,
        builder: (BuildContext context, GoRouterState state) {
          return const RfidPage();
        },
      ),
      GoRoute(
        path: home,
        builder: (BuildContext context, GoRouterState state) {
          return const HomePage();
        },
      ),
      GoRoute(
        path: history,
        builder: (BuildContext context, GoRouterState state) {
          return const HistoryPage();
        },
      ),
      GoRoute(
        path: transactionDetail,
        builder: (BuildContext context, GoRouterState state) {
          final extra = state.extra;
          if (extra is! TransactionData) {
            return const HistoryPage();
          }
          return TransactionDetailPage(transaction: extra);
        },
      ),
      GoRoute(
        path: reward,
        builder: (BuildContext context, GoRouterState state) {
          return const RewardPage();
        },
      ),
      GoRoute(
        path: rewardDetail,
        builder: (BuildContext context, GoRouterState state) {
          final extra = state.extra;
          if (extra is! RewardData) {
            return const RewardPage();
          }
          return RewardDetailPage(reward: extra);
        },
      ),
      GoRoute(
        path: machine,
        builder: (BuildContext context, GoRouterState state) {
          return const MachineLocationPage();
        },
      ),
      GoRoute(
        path: notification,
        builder: (BuildContext context, GoRouterState state) {
          return const NotificationPage();
        },
      ),
      GoRoute(
        path: profile,
        builder: (BuildContext context, GoRouterState state) {
          return const ProfilePage();
        },
      ),
      GoRoute(
        path: editProfile,
        builder: (BuildContext context, GoRouterState state) {
          return const EditProfilePage();
        },
      ),
      GoRoute(
        path: changePassword,
        builder: (BuildContext context, GoRouterState state) {
          return const ChangePasswordPage();
        },
      ),
      GoRoute(
        path: settings,
        builder: (BuildContext context, GoRouterState state) {
          return const SettingsPage();
        },
      ),
      GoRoute(
        path: about,
        builder: (BuildContext context, GoRouterState state) {
          return const AboutPage();
        },
      ),
    ],
  );
}
