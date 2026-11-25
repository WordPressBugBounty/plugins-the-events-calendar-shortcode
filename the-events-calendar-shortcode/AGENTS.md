# AGENTS.md

Guidance for AI agents working with The Events Calendar Shortcode & Block, both agents building a WordPress site with the plugin and coding agents changing this repository.

The plugin lists events from The Events Calendar (`tribe_events` posts) with the `[ecs-list-events]` shortcode. The block and every page builder element save their settings and render through that same shortcode.

## Using the plugin on a site

### Ask the site first (WordPress 6.9+)

The plugin registers two read-only abilities with the WordPress Abilities API (`includes/abilities.php`). They are available over the REST API (`/wp-json/wp-abilities/v1/...`) and the MCP Adapter's default server, for users who can open the plugin's admin page (`edit_published_posts`, filter `ecs_admin_page_capability`):

- `the-events-calendar-shortcode/get-options`: every option on this site with its choices, default, section, the condition that shows it, and which block and page builder elements are available. It reflects add-ons that extend the options, so use it instead of the lists below when you can.
- `the-events-calendar-shortcode/build-shortcode`: turns option values (keys from `get-options`) into an `[ecs-list-events]` shortcode, mapped the same way as the block and page builders. Pass `render: true` to also get the HTML, to check which events it shows. It does not change the site.

Read-only abilities run over GET in the REST API, with the input as query parameters, for example `GET /wp-json/wp-abilities/v1/abilities/the-events-calendar-shortcode/build-shortcode/run?input[limit]=3&input[venue]=true`.

### Where to put the event list

| Editor | What to add | Saved as |
| --- | --- | --- |
| Block editor | The Events Calendar Block (`events-calendar-shortcode/block`) | Block attributes, rendered by `ecs_render_block()` |
| Anywhere shortcodes run | `[ecs-list-events ...]` in content, a Shortcode block, a text widget, or `do_shortcode()` in a template | The shortcode |
| Elementor | The Events Calendar Shortcode & Block widget (`tecs_widget`, under General) | Elementor widget settings |
| Bricks | The Events Calendar Shortcode element (`the-events-calendar-shortcode`) | Bricks element settings |
| WPBakery | The Events Calendar Shortcode & Block element (under Content) | `[tecs_events]` shortcode |
| Divi 4 | The Events Calendar Shortcode & Block module | `[tecs_divi_events]` shortcode |
| Divi 5 | The Events Calendar Shortcode & Block module | `tecs/events` module |

The block is not registered when Divi or Cornerstone is active; use the shortcode or the Divi module there. For a block theme or a page built from blocks, prefer the block, or the shortcode in a `core/shortcode` block when you need an attribute the block sidebar lacks (the block's Advanced/Other key/value rows also pass any attribute through).

### `[ecs-list-events]` attributes

Defaults come from `ecs_fetch_events()` in `the-events-calendar-shortcode.php`. Booleans are the strings `true` and `false`.

| Attribute | Default | Values |
| --- | --- | --- |
| `limit` | `5` | Number of events |
| `cat` | | Event category names or slugs, comma-separated (any match) |
| `month` | | `YYYY-MM`, `current` or `next` |
| `past` | | Any value, such as `yes`, shows past events instead of upcoming ones |
| `key` | `End Date` | `Start Date` hides an event once it starts, instead of once it ends |
| `orderby` | `startdate` | `startdate`, `enddate` or `title` |
| `order` | `ASC` | `ASC` or `DESC` |
| `eventdetails` | `true` | Show the date |
| `venue` | `false` | Show the venue |
| `excerpt` | `false` | `true` (100 characters) or a number of characters |
| `thumb` | `false` | Show the featured image |
| `thumbsize` | `medium` | A registered image size, when `thumb="true"` |
| `thumbwidth`, `thumbheight` | | Pixels; both are needed and replace `thumbsize` |
| `contentorder` | `title, thumbnail, excerpt, date, venue` | Comma-separated order of `title`, `thumbnail`, `excerpt`, `date`, `venue` |
| `viewall` | `false` | Add a link to the main events page |
| `message` | `There are no upcoming %1$s.` | Shown when no events match |
| `schema` | `true` | Add JSON-LD event markup |
| `author` | | Author user ID |

Examples:

```
[ecs-list-events cat="festival" limit="8"]
[ecs-list-events month="current" venue="true" thumb="true" excerpt="150"]
[ecs-list-events past="yes" order="DESC" limit="10"]
```

The Pro version adds designs and options, including a filter bar and pagination; with it active, `get-options` lists them. The output is a plain `ul.ecs-event-list` of `li.ecs-event` items with no styling of its own.

## Changing the code

### Layout

- `the-events-calendar-shortcode.php`: plugin header, the shortcode (`Events_Calendar_Shortcode::ecs_fetch_events()`) and page builder loading.
- `block/`: the block. `init.php` registers it server-side (attributes, filter `ecs_block_attributes`) and maps attributes to the shortcode; the React editor UI is in `block/index.js`, `block/config/` and `block/components/`, built to `static/block.js`.
- `includes/builder-options.php`: the shared option registry, `ecs_builder_options()`. Elementor (`includes/elementor/`), Bricks (`includes/bricks/`), WPBakery (`includes/wpbakery/`), Divi 4 and 5 (`includes/divi/`) and the abilities (`includes/abilities.php`) all build their controls from it and map values with `ecs_builder_settings_to_atts()`. Add a page builder option here once, not in each builder. The file header documents the option schema.
- `admin/`, `templates/`: the Events > Shortcode & Block admin page.
- `.wordpress-org/readme/`: sources of `readme.txt`, which `npm run build` generates.

### Pro shares this code

The Pro plugin (`the-events-calendar-shortcode-pro`, a private repository) copies this repository into its `core/` directory with `npm run sync:core` and loads it instead of the free plugin. That is why every function is wrapped in `if ( ! function_exists() )` and why both plugins share the `the-events-calendar-shortcode` text domain. Pro extends the free code only through filters, such as `ecs_shortcode_atts`, `ecs_block_attributes`, `ecs_builder_options` and the per-builder design filters (`ecs_<builder>_designs`, `ecs_<builder>_design_default`). Keep those filters and their arguments stable, and land free changes first, then sync them into Pro.

This plugin is on WordPress.org: do not add Pro-only code, or documentation of Pro-only options beyond what the readme already says.

### Build and checks

- `npm ci`, then `npm run build` (webpack block build, minified admin JS, `readme.txt`). Generated files in `static/` and `readme.txt` are not committed.
- CI (`.github/workflows/build.yml`) lints PHP with `php -l` and runs the build.

### Conventions

- Branch from `dev` and open pull requests against `dev`.
- When mentioning a pull request or issue anywhere (pull request descriptions, commit messages, comments, docs, reports), link it by its full URL, such as `https://github.com/Event-Calendar-Newsletter/the-events-calendar-shortcode-pro/pull/317`, not a bare `#317`.
- New and changed docblocks use `@since [version]`; the release tooling fills in the number. Do not bump the plugin version.
- Add a `.changelogs/<slug>.yml` entry (`significance`, `type`, `entry`) for changes users see, not for repository-only changes such as docs, CI or build tooling.
