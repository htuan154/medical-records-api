import 'package:flutter/material.dart';
import 'navbar_item.dart';
import '../screens/profile_screens/profile_screen.dart';
import '../screens/home_screens/home_screen.dart';

class NavbarController extends ChangeNotifier {
  int _selectedIndex = 0;
  late final List<NavbarItem> items;

  NavbarController()
    : items = [
        NavbarItem(label: 'Home', icon: Icons.home, page: HomeScreenContent()),
        NavbarItem(label: 'Profile', icon: Icons.person, page: ProfileScreen()),
      ];

  int get selectedIndex => _selectedIndex;
  NavbarItem get currentItem => items[_selectedIndex];

  Widget getCurrentPage() {
    return items[_selectedIndex].page;
  }

  void select(int index) {
    if (index != _selectedIndex) {
      _selectedIndex = index;
      notifyListeners();
    }
  }
}
