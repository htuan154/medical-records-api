import 'package:flutter/material.dart';
import 'navbar_controller.dart';
import 'package:provider/provider.dart';

class AppLayout extends StatelessWidget {
  const AppLayout({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (_) => NavbarController(),
      child: Consumer<NavbarController>(
        builder: (context, controller, _) {
          return Scaffold(
            backgroundColor: Colors.grey.shade50,
            body: Stack(
              children: [
                Positioned.fill(child: controller.getCurrentPage()),
                Positioned(
                  left: 0,
                  right: 0,
                  bottom: 0,
                  child: Container(
                    color: Colors.white,
                    child: BottomNavigationBar(
                      type: BottomNavigationBarType.fixed, // Đảm bảo hiển thị đúng với 4 tab
                      currentIndex: controller.selectedIndex,
                      onTap: controller.select,
                      items: controller.items
                          .map(
                            (item) => BottomNavigationBarItem(
                              icon: Icon(item.icon),
                              label: item.label,
                            ),
                          )
                          .toList(),
                    ),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}
