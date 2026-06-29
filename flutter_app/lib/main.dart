import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:firebase_core/firebase_core.dart';
import 'config/app_config.dart';
import 'providers/auth_provider.dart';
import 'screens/login_screen.dart';
import 'screens/bill_list_screen.dart';
import 'screens/bill_detail_screen.dart';
import 'services/notification_service.dart';

final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp();

  SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
    statusBarColor: Color(AppConfig.primaryColor),
    statusBarIconBrightness: Brightness.light,
    systemNavigationBarColor: Color(0xFFF5F5F4),
    systemNavigationBarIconBrightness: Brightness.dark,
  ));

  runApp(const BillApproverApp());
}

class BillApproverApp extends StatelessWidget {
  const BillApproverApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (_) => AuthProvider()..checkAuth(),
      child: MaterialApp(
        title: 'ScanOCR',
        debugShowCheckedModeBanner: false,
        navigatorKey: navigatorKey,
        routes: {
          '/bill-detail': (context) {
            final scanId = ModalRoute.of(context)!.settings.arguments as int;
            return BillDetailScreen(scanId: scanId);
          },
        },
        theme: ThemeData(
          useMaterial3: true,
          colorScheme: ColorScheme.fromSeed(
            seedColor: const Color(AppConfig.primaryColor),
            primary: const Color(AppConfig.primaryColor),
            secondary: const Color(AppConfig.accentColor),
            surface: const Color(AppConfig.surfaceColor),
            brightness: Brightness.light,
          ),
          scaffoldBackgroundColor: const Color(0xFFF5F5F4),
          appBarTheme: const AppBarTheme(
            backgroundColor: Color(AppConfig.primaryColor),
            foregroundColor: Colors.white,
            elevation: 0,
            centerTitle: false,
            titleTextStyle: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w600,
              color: Colors.white,
            ),
          ),
          cardTheme: CardThemeData(
            elevation: 0,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
              side: const BorderSide(color: Color(0xFFE7E5E4)),
            ),
            color: Colors.white,
          ),
          elevatedButtonTheme: ElevatedButtonThemeData(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(AppConfig.primaryColor),
              foregroundColor: Colors.white,
              elevation: 0,
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              textStyle: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
            ),
          ),
          inputDecorationTheme: InputDecorationTheme(
            filled: true,
            fillColor: Colors.white,
            contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: const BorderSide(color: Color(0xFFD6D3D1)),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: const BorderSide(color: Color(0xFFD6D3D1)),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: const BorderSide(color: Color(AppConfig.primaryColor), width: 1.5),
            ),
            hintStyle: const TextStyle(color: Color(0xFFA8A29E), fontSize: 14),
          ),
          tabBarTheme: const TabBarThemeData(
            labelColor: Colors.white,
            unselectedLabelColor: Colors.white70,
            indicatorColor: Colors.white,
            labelStyle: TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
            unselectedLabelStyle: TextStyle(fontSize: 13),
          ),
        ),
        home: const AuthGate(),
      ),
    );
  }
}

class AuthGate extends StatefulWidget {
  const AuthGate({super.key});

  @override
  State<AuthGate> createState() => _AuthGateState();
}

class _AuthGateState extends State<AuthGate> {
  @override
  void initState() {
    super.initState();
    _initNotifications();
  }

  Future<void> _initNotifications() async {
    try {
      await NotificationService.initialize(navKey: navigatorKey);
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<AuthProvider>(
      builder: (context, auth, _) {
        if (auth.isLoading) {
          return const Scaffold(
            body: Center(
              child: CircularProgressIndicator(color: Color(AppConfig.primaryColor)),
            ),
          );
        }
        return auth.isLoggedIn ? const BillListScreen() : const LoginScreen();
      },
    );
  }
}
