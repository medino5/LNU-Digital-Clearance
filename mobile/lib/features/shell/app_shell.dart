import 'package:flutter/material.dart';

import '../../screens/dashboard_screen.dart';
import '../history/history_screen.dart';
import '../profile/profile_screen.dart';

class AppShell extends StatefulWidget {
  const AppShell({super.key});

  @override
  State<AppShell> createState() => _AppShellState();
}

class _AppShellState extends State<AppShell> {
  int _selectedIndex = 0;

  static const Color _lnuGold = Color(0xFFC9A84C);
  static const Color _lnuNavy = Color(0xFF1B3A6B);

  late final List<Widget> _screens = const [
    DashboardScreen(),
    HistoryScreen(),
    ProfileScreen(),
  ];

  Future<bool> _handleWillPop() async {
    if (_selectedIndex != 0) {
      setState(() => _selectedIndex = 0);
      return false;
    }
    return true;
  }

  String _titleForIndex(int index) {
    switch (index) {
      case 0:
        return 'Dashboard';
      case 1:
        return 'Clearance History';
      case 2:
        return 'Profile';
      default:
        return 'Dashboard';
    }
  }

  BottomNavigationBarItem _navItem({
    required String label,
    required IconData activeIcon,
    required IconData inactiveIcon,
  }) {
    return BottomNavigationBarItem(
      icon: Icon(inactiveIcon),
      activeIcon: Icon(activeIcon),
      label: label,
    );
  }

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: _handleWillPop,
      child: Scaffold(
        appBar: AppBar(
          backgroundColor: _lnuNavy,
          automaticallyImplyLeading: false,
          title: Text(
            _titleForIndex(_selectedIndex),
            style: const TextStyle(color: Colors.white),
          ),
          actions: const [
            // LDCS-46: notification bell goes here
          ],
        ),
        body: IndexedStack(
          index: _selectedIndex,
          children: _screens,
        ),
        bottomNavigationBar: BottomNavigationBar(
          currentIndex: _selectedIndex,
          onTap: (index) => setState(() => _selectedIndex = index),
          selectedItemColor: _lnuGold,
          unselectedItemColor: const Color(0xFF9E9E9E),
          backgroundColor: Colors.white,
          type: BottomNavigationBarType.fixed,
          selectedLabelStyle: const TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 12,
          ),
          unselectedLabelStyle: const TextStyle(fontSize: 12),
          elevation: 8,
          items: [
            _navItem(
              label: 'Dashboard',
              activeIcon: Icons.home,
              inactiveIcon: Icons.home_outlined,
            ),
            _navItem(
              label: 'History',
              activeIcon: Icons.history,
              inactiveIcon: Icons.history_outlined,
            ),
            _navItem(
              label: 'Profile',
              activeIcon: Icons.person,
              inactiveIcon: Icons.person_outline,
            ),
          ],
        ),
      ),
    );
  }
}
