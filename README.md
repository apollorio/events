# Apollo Events Manager

Modern event management plugin for WordPress with Motion.dev animations, ShadCN components, and Tailwind CSS integration.

## Description

Apollo Events Manager provides a complete event management solution for the Apollo::Rio ecosystem. It handles events, DJs, venues (locals), and user interactions with a modern, responsive interface.

## Requirements

- **WordPress:** 6.0 or higher
- **PHP:** 8.3 or higher
- **Dependencies:**
  - `apollo-core` (required) - Core utilities and integration bridge
  - `apollo-social` (optional) - Enhanced social features

## Installation

1. Ensure `apollo-core` plugin is installed and activated
2. Upload `apollo-events-manager` folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress admin
4. Configure settings under Events > Settings

## Features

### MVP Features (Phase 1-4 Complete)

- ✅ **Event Management** - Create, edit, and manage events with rich metadata
- ✅ **DJ Management** - Artist profiles with social links and media
- ✅ **Venue Management** - Location profiles with geocoding and maps
- ✅ **REST API** - Full REST API at `/wp-json/apollo/v1/`
- ✅ **AJAX Handlers** - Optimized AJAX for modal loading and filtering
- ✅ **Bookmarks System** - User favorites with database storage
- ✅ **Statistics Tracking** - View counts and analytics
- ✅ **Security Hardened** - Input sanitization, output escaping, nonce verification

### REST API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/apollo/v1/eventos` | GET | List events with filters |
| `/apollo/v1/evento/{id}` | GET | Get single event |
| `/apollo/v1/categorias` | GET | List event categories |
| `/apollo/v1/locais` | GET | List venues |
| `/apollo/v1/my-events` | GET | User's events (auth required) |
| `/apollo/v1/salvos` | GET | User's bookmarks (auth required) |
| `/apollo/v1/salvos/{id}` | POST | Toggle bookmark (auth required) |

### AJAX Actions

**Public:**
- `filter_events` - Filter events by category, date, location
- `load_event_single` - Load single event content
- `apollo_get_event_modal` - Load event modal HTML
- `apollo_track_event_view` - Track event views
- `apollo_get_event_stats` - Get event statistics

**Authenticated:**
- `toggle_favorite` - Toggle event favorite
- `apollo_toggle_bookmark` - Toggle event bookmark
- `apollo_save_profile` - Save user profile
- `apollo_submit_event_comment` - Submit event comment
- `apollo_mod_approve_event` - Approve event (moderator)
- `apollo_mod_reject_event` - Reject event (moderator)

## Custom Post Types

| CPT | Slug | Description |
|-----|------|-------------|
| Events | `event_listing` | Main event posts |
| DJs | `event_dj` | Artist/DJ profiles |
| Venues | `event_local` | Venue/location profiles |

## Taxonomies

- `event_listing_category` - Event categories
- `event_sounds` - Music genres/sounds

## Meta Keys

### Event Meta
- `_event_start_date` - Event start date (YYYY-MM-DD)
- `_event_end_date` - Event end date
- `_event_start_time` - Start time (HH:MM)
- `_event_end_time` - End time
- `_event_dj_ids` - Array of DJ post IDs
- `_event_local_ids` - Venue post ID
- `_event_banner` - Banner image URL
- `_event_location` - Text location (fallback)
- `_tickets_ext` - External tickets URL
- `_cupom_ario` - Discount coupon code

### DJ Meta
- `_dj_image` - Profile image
- `_dj_banner` - Banner image
- `_dj_website` - Official website
- `_dj_instagram`, `_dj_facebook`, etc. - Social links
- `_dj_soundcloud`, `_dj_spotify`, etc. - Music platforms

### Local Meta
- `_local_address` - Street address
- `_local_city` - City
- `_local_state` - State
- `_local_latitude` - Latitude coordinate
- `_local_longitude` - Longitude coordinate
- `_local_website` - Venue website
- `_local_image_1` to `_local_image_5` - Gallery images

## Security

This plugin has been security audited (Phase 3) with:
- Input sanitization on all REST and AJAX endpoints
- Output escaping in all templates
- CSRF/nonce verification on all state-changing actions
- SQL injection prevention with `$wpdb->prepare()`

See `SECURITY-DELTA-REPORT.md` for full audit details.

## Testing

Run PHPUnit tests:

```bash
cd wp-content/plugins/apollo-events-manager
./vendor/bin/phpunit
```

Test suites:
- `test-rest-api.php` - REST API endpoint tests
- `test-mvp-flows.php` - MVP flow integration tests
- `test-bookmarks.php` - Bookmarks system tests

## MVP E2E Checklist

### Event CRUD Flow
- [ ] Create event with title, date, DJ, and venue
- [ ] Edit event metadata
- [ ] View event on frontend (single page)
- [ ] View event in modal (portal page)
- [ ] Delete/trash event

### DJ Management Flow
- [ ] Create DJ profile
- [ ] Add social links and media
- [ ] Connect DJ to event
- [ ] View DJ profile page

### Venue Management Flow
- [ ] Create venue with address
- [ ] Auto-geocoding on save
- [ ] Connect venue to event
- [ ] View venue profile page

### User Interaction Flow
- [ ] Bookmark event (logged in)
- [ ] Remove bookmark
- [ ] View bookmarked events
- [ ] Submit event comment

### Statistics Flow
- [ ] Track page view
- [ ] Track modal view
- [ ] View event statistics (admin)

## Changelog

### 1.0.0 (2025-12-11)
- Initial MVP release
- Security hardening (Phase 3)
- PHP 8.3 compatibility (Phase 4)
- PHPUnit test suite

## License

GPL-2.0-or-later

## Author

Apollo::Rio Team - https://apollo.rio.br

