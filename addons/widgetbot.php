<?php
/**
 * Addon Name: WidgetBot Discord Integration
 * Version: 7.3 (Free Tier Optimized)
 */

// 1. Sidebar CSS (Right side, 50% width)
add_hook('head_bottom', function() {
    echo '
    <style>
        iframe[src*="widgetbot.io"] {
            height: 100vh !important;
            width: 50vw !important;
            max-height: 100vh !important;
            max-width: 50vw !important;
            top: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            border-radius: 0 !important;
            border: none !important;
            box-shadow: -5px 0 15px rgba(0,0,0,0.3) !important;
            position: fixed !important;
            z-index: 99999 !important;
        }
    </style>';
});

// 2. Navbar Link
add_hook('navbar_end', function() {
    echo '
    <li class="nav-item">
        <a class="nav-link position-relative" href="#" onclick="openCommunityChat(event)">
            Community Chat
            <span id="chat-badge" class="badge rounded-pill bg-danger d-none" 
                  style="font-size: 0.65rem; vertical-align: top; margin-left: 2px;">
                0
            </span>
        </a>
    </li>';
});

// 3. JavaScript Logic
add_hook('footer_bottom', function() {
    $guestId = substr(md5($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'), 0, 6);
    ?>
    <script src="https://cdn.jsdelivr.net/npm/@widgetbot/crate@3" async defer></script>
    <script>
      let unreadCount = 0;

      const updateBadge = () => {
          const badge = document.getElementById('chat-badge');
          if (!badge) return;
          if (unreadCount > 0) {
              badge.innerText = unreadCount > 99 ? '99+' : unreadCount;
              badge.classList.remove('d-none');
          } else {
              badge.classList.add('d-none');
          }
      };

      window.openCommunityChat = function(e) {
          if (e) e.preventDefault();
          if (window.myCrate) {
              window.myCrate.show(); 
              window.myCrate.toggle(true);
              unreadCount = 0;
              updateBadge();
          }
      };

      (function() {
        const initWidget = () => {
          if (typeof Crate === 'undefined') {
              setTimeout(initWidget, 200);
              return;
          }

          // White Chat Icon (Better contrast on Blue background)
          const whiteChatIcon = "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iI0ZGRkZGRiI+PHBhdGggZD0iTTEyIDJDNi40ODIgMiAyIDUuNTgyIDIgMTBjMCAxLjU5MS4zODMgMy4wOTEgMS4wNjEgNC40MzhMMiAyMmw3LjU2Mi0yLjA2MUMxMC45MDkgMjAuNjE3IDEyLjQwOSAyMSAxNCAyMWM1LjUxOCAwIDEwLTQuNDgyIDEwLTEwUzE3LjUxOCAyIDEyIDJ6Ii8+PC9zdmc+";

          window.myCrate = new Crate({
              server: '201789112133484553', 
              channel: '1201038181584343110',
              shard: 'https://e.widgetbot.io',
              username: 'Guest#<?php echo $guestId; ?>',
              defer: false,
              color: '#5865F2', // Discord Blue (Works on free tier)
              glyph: [whiteChatIcon, '60%'],
              css: `
                .button { 
                    border-radius: 8px !important; 
                }
              `
          });

          window.myCrate.hide();

          window.myCrate.on('message', () => {
              if (window.myCrate && !window.myCrate.isOpened) {
                  unreadCount++;
                  updateBadge();
              }
          });

          window.myCrate.on('toggle', (open) => {
              if (open) {
                  unreadCount = 0;
                  updateBadge();
              }
          });
        };

        if (document.readyState === 'complete') {
            initWidget();
        } else {
            window.addEventListener('load', initWidget);
        }
      })();
    </script>
    <?php
});

// 3. Inject Privacy Policy Disclosure
add_hook('privacy_policy_disclosures', function() {
    echo '
    <section class="mb-3 ps-3 border-start border-primary">
        <h4 class="h6 fw-semibold mb-1">Community Chat (Discord & WidgetBot)</h4>
        <p class="text-muted small mb-2">
            We provide an integrated chat feature via WidgetBot and Discord. By using the chat widget:
        </p>
        <ul class="text-muted small">
            <li>Your IP address is used locally to generate a temporary guest username.</li>
            <li>Messages sent via the widget are processed by Discord.com and stored according to their retention policies.</li>
            <li>WidgetBot may use essential cookies to maintain your chat session across page navigation.</li>
        </ul>
        <p class="text-muted small mb-0">
            For more details, please see the <a href="https://discord.com/privacy" target="_blank" rel="noopener">Discord Privacy Policy</a>.
        </p>
    </section>';
});