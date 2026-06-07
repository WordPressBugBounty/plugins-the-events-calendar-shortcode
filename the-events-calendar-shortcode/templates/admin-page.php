<?php
$show_upgrades = apply_filters( 'ecs_show_upgrades', true );
$utm_base      = 'utm_source=plugin&utm_medium=link&utm_campaign=full-documentation';

// Pro (and other extensions) inject additional shortcode option docs via these hooks.
// Capture them so they can render below the core options table instead of inside it,
// since the injected markup is <h3>/<code> blocks rather than table rows.
ob_start();
do_action( 'ecs_admin_page_options_after_cat' );
do_action( 'ecs_admin_page_options_after' );
$injected_options = trim( ob_get_clean() );
?>
<div class="wrap">
  <div class="w-full max-w-5xl mx-auto">

    <div class="bg-white rounded-lg shadow-md p-8 mb-6">
      <h1 class="text-2xl font-bold text-gray-900 mb-2"><?php _e( 'The Events Calendar Shortcode &amp; Block', 'the-events-calendar-shortcode' ); ?></h1>
      <p class="text-base text-gray-600 mb-6"><?php echo sprintf( esc_html__( 'Display lists of your events wherever you want them on your site. For example, show the next 8 events in the category "%s":', 'the-events-calendar-shortcode' ), 'festival' ); ?></p>
      <code class="text-sm sm:text-base inline-flex text-left items-center bg-gray-800 text-white rounded-lg p-4 pl-6 mb-6">[ecs-list-events cat='festival' limit='8']</code>

      <div class="flex flex-row gap-4 mt-2">
        <a class="bg-[#EB6924] rounded-lg px-6 py-3 text-white shadow-md text-center no-underline hover:text-white font-medium text-sm" href="https://eventcalendarnewsletter.com/the-events-calendar-shortcode-short-walk-through-video/?<?php echo esc_attr( $utm_base ); ?>&utm_content=top" target="_blank"><?php echo esc_html( __( 'Watch Walk-Through Video', 'the-events-calendar-shortcode' ) ); ?></a>
        <a class="rounded-lg px-6 py-3 shadow-md text-center no-underline font-medium text-sm text-gray-700 bg-gray-100 hover:text-gray-700 border border-gray-200" href="https://eventcalendarnewsletter.com/events-calendar-shortcode-pro-options/?<?php echo $show_upgrades ? 'free=1&' : ''; ?><?php echo esc_attr( $utm_base ); ?>&utm_content=top" target="_blank"><?php echo esc_html( __( 'View Full Documentation', 'the-events-calendar-shortcode' ) ); ?></a>
      </div>
    </div>

    <?php do_action( 'ecs_admin_page_options_top' ); ?>

    <?php if ( $show_upgrades ): ?>
    <div id="ecs-pro-description" class="bg-white rounded-lg shadow-md overflow-hidden mb-6 border-t-4 border-[#EB6924]">
      <div class="p-8">
        <h2 class="text-xl font-bold text-gray-900 mb-2"><?php echo esc_html__( 'Unlock More Designs with Pro', 'the-events-calendar-shortcode' ); ?></h2>
        <p class="text-gray-600 text-base mb-6"><?php echo sprintf( esc_html__( '%sThe Events Calendar Shortcode & Block Pro%s adds ready-made designs and powerful display options. Click any design to see it in action:', 'the-events-calendar-shortcode' ), '<strong><a href="https://eventcalendarnewsletter.com/the-events-calendar-shortcode/">', '</a></strong>' ); ?></p>

        <div id="ecs-pro-designs" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
          <?php
          $designs = [
            [ 'image' => 'the-events-calendar-shortcode-pro-default.png',   'label' => __( 'Default design', 'the-events-calendar-shortcode' ),         'demo' => 'default-design' ],
            [ 'image' => 'the-events-calendar-shortcode-pro-compact.png',   'label' => __( 'Compact design', 'the-events-calendar-shortcode' ),         'demo' => 'compact-design' ],
            [ 'image' => 'the-events-calendar-shortcode-pro-calendar.png',  'label' => __( 'Calendar design', 'the-events-calendar-shortcode' ),        'demo' => 'calendar-design' ],
            [ 'image' => 'the-events-calendar-shortcode-pro-columns.png',   'label' => __( 'Columns / photo design', 'the-events-calendar-shortcode' ), 'demo' => 'columns-photos-horizontal-design' ],
            [ 'image' => 'the-events-calendar-shortcode-pro-table.png',     'label' => __( 'Table design', 'the-events-calendar-shortcode' ),           'demo' => 'table-design' ],
            [ 'image' => 'the-events-calendar-shortcode-pro-grouped.png',   'label' => __( 'Grouped design', 'the-events-calendar-shortcode' ),         'demo' => 'grouped-design' ],
            [ 'image' => 'the-events-calendar-shortcode-filter-bar.png',    'label' => __( 'Filter bar', 'the-events-calendar-shortcode' ),             'demo' => 'filter-bar' ],
            [ 'image' => 'the-events-calendar-shortcode-pagination.png',    'label' => __( 'Pagination', 'the-events-calendar-shortcode' ),             'demo' => 'pagination' ],
            [ 'image' => 'the-events-calendar-shortcode-pro-shortcode-variables.png', 'label' => __( 'Shortcode variables', 'the-events-calendar-shortcode' ), 'demo' => 'custom-design' ],
          ];
          foreach ( $designs as $design ):
          ?>
            <a class="group block no-underline text-center" target="_blank" href="<?php echo esc_url( 'https://demo.eventcalendarnewsletter.com/the-events-calendar-shortcode/' . $design['demo'] . '/?utm_source=plugin&utm_medium=link&utm_campaign=tecs-design-demo&utm_content=' . $design['demo'] ); ?>">
              <div class="overflow-hidden rounded-lg border border-gray-200 shadow-md mb-2">
                <img alt="<?php echo esc_attr( $design['label'] ); ?>" class="w-full h-auto object-cover" src="<?php echo plugins_url( '/static/images/' . $design['image'], TECS_CORE_PLUGIN_FILE ); ?>">
              </div>
              <span class="text-sm font-medium text-gray-700 group-hover:text-[#EB6924]"><?php echo esc_html( $design['label'] ); ?></span>
            </a>
          <?php endforeach; ?>
        </div>

        <div class="border-t border-gray-100 pt-6">
          <h3 class="text-base font-bold text-gray-900 mb-4"><?php echo esc_html__( 'Plus more options, including:', 'the-events-calendar-shortcode' ); ?></h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-2 text-sm text-gray-600 mb-8">
            <div class="before:content-['\2713'] before:mr-2 before:text-[#008a20]"><?php echo esc_html__( 'Filter by tag or location', 'the-events-calendar-shortcode' ); ?></div>
            <div class="before:content-['\2713'] before:mr-2 before:text-[#008a20]"><?php echo esc_html__( 'Filter bar for visitors', 'the-events-calendar-shortcode' ); ?></div>
            <div class="before:content-['\2713'] before:mr-2 before:text-[#008a20]"><?php echo esc_html__( 'Pagination support', 'the-events-calendar-shortcode' ); ?></div>
            <div class="before:content-['\2713'] before:mr-2 before:text-[#008a20]"><?php echo esc_html__( 'Single event display', 'the-events-calendar-shortcode' ); ?></div>
            <div class="before:content-['\2713'] before:mr-2 before:text-[#008a20]"><?php echo esc_html__( 'Day & year filters', 'the-events-calendar-shortcode' ); ?></div>
            <div class="before:content-['\2713'] before:mr-2 before:text-[#008a20]"><?php echo esc_html__( 'Featured events only', 'the-events-calendar-shortcode' ); ?></div>
            <div class="before:content-['\2713'] before:mr-2 before:text-[#008a20]"><?php echo esc_html__( 'Custom button links & colors', 'the-events-calendar-shortcode' ); ?></div>
            <div class="before:content-['\2713'] before:mr-2 before:text-[#008a20]"><?php echo esc_html__( 'Custom templates &amp; shortcode variables', 'the-events-calendar-shortcode' ); ?></div>
          </div>

          <div class="flex flex-col sm:flex-row gap-3">
            <a class="bg-[#EB6924] rounded-lg px-6 py-3 text-white shadow-md text-center no-underline hover:text-white font-medium text-sm" target="_blank" href="https://eventcalendarnewsletter.com/the-events-calendar-shortcode?utm_source=plugin&utm_medium=link&utm_campaign=tecs-help-after-options&utm_content=showcase"><?php echo esc_html__( 'Get Pro', 'the-events-calendar-shortcode' ); ?></a>
            <a class="rounded-lg px-6 py-3 text-center no-underline font-medium text-sm text-[#EB6924] hover:text-[#EB6924] border border-[#EB6924]" target="_blank" href="https://demo.eventcalendarnewsletter.com/the-events-calendar-shortcode/"><?php echo esc_html__( 'See It in Action', 'the-events-calendar-shortcode' ); ?></a>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="mb-6">
      <nav class="flex border-b border-gray-200" role="tablist">
        <button type="button" class="ecs-tab ecs-tab-active px-6 py-3 text-sm font-medium border-b-2 border-[#EB6924] text-[#EB6924] bg-transparent cursor-pointer" data-tab="quick-start"><?php echo esc_html( __( 'Quick Start', 'the-events-calendar-shortcode' ) ); ?></button>
        <button type="button" class="ecs-tab px-6 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 bg-transparent cursor-pointer hover:text-gray-700" data-tab="shortcode-ref"><?php echo esc_html( __( 'Shortcode Reference', 'the-events-calendar-shortcode' ) ); ?></button>
        <button type="button" class="ecs-tab px-6 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 bg-transparent cursor-pointer hover:text-gray-700" data-tab="styling"><?php echo esc_html( __( 'Styling &amp; CSS', 'the-events-calendar-shortcode' ) ); ?></button>
      </nav>
    </div>

    <!-- Tab: Quick Start -->
    <div id="tab-quick-start" class="ecs-tab-panel">

      <div class="bg-white rounded-lg shadow-md p-8 mb-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4"><?php echo esc_html( __( 'Using the Block', 'the-events-calendar-shortcode' ) ); ?></h2>
        <div class="flex flex-col sm:flex-row gap-6 items-start">
          <div class="sm:w-1/2">
            <img class="rounded-lg max-w-full h-auto border border-gray-200" src="<?php echo esc_attr( plugins_url( 'static/images/the-events-calendar-block.png', TECS_CORE_PLUGIN_FILE ) ); ?>" alt="<?php echo esc_attr( __( 'The Events Calendar Block', 'the-events-calendar-shortcode' ) ); ?>">
          </div>
          <div class="sm:w-1/2">
            <p class="text-gray-600 text-base mb-4"><?php echo esc_html( __( 'When using the WordPress editor, select The Events Calendar Block. After saving, the list of your events will display.', 'the-events-calendar-shortcode' ) ); ?></p>
            <p class="text-gray-600 text-base"><?php echo esc_html( __( 'The block contains most of the options listed in the Shortcode Reference tab. Otherwise, you can use the Advanced/Other option to enter a shortcode option manually.', 'the-events-calendar-shortcode' ) ); ?></p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-md p-8 mb-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4"><?php echo esc_html( __( 'Using a Shortcode', 'the-events-calendar-shortcode' ) ); ?></h2>
        <p class="text-gray-600 text-base mb-4"><?php echo esc_html( __( 'Add the following shortcode to any page or post to display your events:', 'the-events-calendar-shortcode' ) ); ?></p>
        <code class="text-sm sm:text-base inline-flex text-left items-center bg-gray-800 text-white rounded-lg p-4 pl-6">[ecs-list-events]</code>
        <p class="text-gray-500 text-sm mt-4"><?php echo esc_html( __( 'Customize the output with options like category, limit, and order. See the Shortcode Reference tab for all options.', 'the-events-calendar-shortcode' ) ); ?></p>
      </div>

    </div>

    <!-- Tab: Shortcode Reference -->
    <div id="tab-shortcode-ref" class="ecs-tab-panel hidden">

      <?php do_action( 'ecs_admin_page_options_before' ); ?>

      <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="p-6 border-b border-gray-200">
          <h2 class="text-xl font-bold text-gray-900 mb-1"><?php echo esc_html( __( 'Shortcode Options', 'the-events-calendar-shortcode' ) ); ?></h2>
          <p class="text-gray-500 text-sm"><?php echo esc_html( __( 'All options can be combined. Example:', 'the-events-calendar-shortcode' ) ); ?>
            <code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1">[ecs-list-events cat='festival' limit='3' order='DESC']</code>
          </p>
        </div>

        <table class="w-full text-sm text-left">
          <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
            <tr>
              <th class="px-6 py-3 font-medium"><?php echo esc_html( __( 'Option', 'the-events-calendar-shortcode' ) ); ?></th>
              <th class="px-6 py-3 font-medium"><?php echo esc_html( __( 'Description', 'the-events-calendar-shortcode' ) ); ?></th>
              <th class="px-6 py-3 font-medium"><?php echo esc_html( __( 'Example', 'the-events-calendar-shortcode' ) ); ?></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 font-mono font-medium text-[#EB6924]">cat</td>
              <td class="px-6 py-4 text-gray-600"><?php echo esc_html( __( 'Filter by one or more categories (comma-separated).', 'the-events-calendar-shortcode' ) ); ?></td>
              <td class="px-6 py-4"><code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1">cat='festival, workshops'</code></td>
            </tr>

            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 font-mono font-medium text-[#EB6924]">limit</td>
              <td class="px-6 py-4 text-gray-600"><?php echo esc_html( __( 'Total number of events to show. Default is 5.', 'the-events-calendar-shortcode' ) ); ?></td>
              <td class="px-6 py-4"><code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1">limit='3'</code></td>
            </tr>

            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 font-mono font-medium text-[#EB6924]">order</td>
              <td class="px-6 py-4 text-gray-600"><?php echo esc_html( __( "Order of events: 'ASC' or 'DESC'. Default is 'ASC', based on event date.", 'the-events-calendar-shortcode' ) ); ?></td>
              <td class="px-6 py-4"><code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1">order='DESC'</code></td>
            </tr>

            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 font-mono font-medium text-[#EB6924]">eventdetails</td>
              <td class="px-6 py-4 text-gray-600"><?php echo esc_html( __( "Show or hide the date. 'true' or 'false'. Default is true.", 'the-events-calendar-shortcode' ) ); ?></td>
              <td class="px-6 py-4"><code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1">eventdetails='false'</code></td>
            </tr>

            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 font-mono font-medium text-[#EB6924]">venue</td>
              <td class="px-6 py-4 text-gray-600"><?php echo esc_html( __( "Show or hide the venue. 'true' or 'false'. Default is false.", 'the-events-calendar-shortcode' ) ); ?></td>
              <td class="px-6 py-4"><code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1">venue='true'</code></td>
            </tr>

            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 font-mono font-medium text-[#EB6924]">excerpt</td>
              <td class="px-6 py-4 text-gray-600"><?php echo esc_html( __( "Show excerpt or set length. 'true', 'false', or a number. Default is false.", 'the-events-calendar-shortcode' ) ); ?></td>
              <td class="px-6 py-4"><code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1">excerpt='300'</code></td>
            </tr>

            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 font-mono font-medium text-[#EB6924]">thumb</td>
              <td class="px-6 py-4 text-gray-600"><?php echo esc_html( sprintf( __( "Show thumbnail/featured image. Use %s and %s for custom size, or %s for a preset size.", 'the-events-calendar-shortcode' ), 'thumbwidth', 'thumbheight', 'thumbsize' ) ); ?></td>
              <td class="px-6 py-4">
                <code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1">thumb='true'</code><br>
                <code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1 mt-1 inline-block">thumbsize='large'</code>
              </td>
            </tr>

            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 font-mono font-medium text-[#EB6924]">message</td>
              <td class="px-6 py-4 text-gray-600"><?php echo esc_html( sprintf( __( "Message when there are no events. Defaults to '%s'.", 'the-events-calendar-shortcode' ), translate( 'There are no upcoming events at this time.', 'tribe-events-calendar' ) ) ); ?></td>
              <td class="px-6 py-4"><code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1">message='Check back soon!'</code></td>
            </tr>

            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 font-mono font-medium text-[#EB6924]">viewall</td>
              <td class="px-6 py-4 text-gray-600">
                <?php if ( function_exists( 'tribe_get_event_label_plural' ) ): ?>
                  <?php echo esc_html( sprintf( __( "Show or hide the '%s' link. Default is true.", 'the-events-calendar-shortcode' ), sprintf( __( 'View All %s', 'the-events-calendar' ), tribe_get_event_label_plural() ) ) ); ?>
                <?php else: ?>
                  <?php echo esc_html( __( "Show or hide the 'View All' link. Default is true.", 'the-events-calendar-shortcode' ) ); ?>
                <?php endif; ?>
              </td>
              <td class="px-6 py-4"><code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1">viewall='false'</code></td>
            </tr>

            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 font-mono font-medium text-[#EB6924]">contentorder</td>
              <td class="px-6 py-4 text-gray-600"><?php echo esc_html( sprintf( __( 'Order of content elements, comma-separated. Defaults to %s.', 'the-events-calendar-shortcode' ), 'title, thumbnail, excerpt, date, venue' ) ); ?></td>
              <td class="px-6 py-4"><code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1">contentorder='title, date, excerpt'</code></td>
            </tr>

            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 font-mono font-medium text-[#EB6924]">month</td>
              <td class="px-6 py-4 text-gray-600"><?php echo esc_html( sprintf( __( "Show events for a specific month. Use '%s' for current, '%s' for next, or a specific month.", 'the-events-calendar-shortcode' ), 'current', 'next' ) ); ?></td>
              <td class="px-6 py-4"><code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1">month='current'</code></td>
            </tr>

            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 font-mono font-medium text-[#EB6924]">past</td>
              <td class="px-6 py-4 text-gray-600"><?php echo esc_html( __( 'Show events that have already happened.', 'the-events-calendar-shortcode' ) ); ?></td>
              <td class="px-6 py-4"><code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1">past='yes'</code></td>
            </tr>

            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 font-mono font-medium text-[#EB6924]">key</td>
              <td class="px-6 py-4 text-gray-600"><?php echo esc_html( __( 'Hide events when start date has passed (instead of end date). Also orders by start date.', 'the-events-calendar-shortcode' ) ); ?></td>
              <td class="px-6 py-4"><code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1">key='start date'</code></td>
            </tr>

            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 font-mono font-medium text-[#EB6924]">orderby</td>
              <td class="px-6 py-4 text-gray-600"><?php echo esc_html( __( 'Order by end date or title instead of the default start date.', 'the-events-calendar-shortcode' ) ); ?></td>
              <td class="px-6 py-4">
                <code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1">orderby='enddate'</code><br>
                <code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1 mt-1 inline-block">orderby='title'</code>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <?php if ( $injected_options ): ?>
      <div class="bg-white rounded-lg shadow-md p-8 mb-6 ecs-injected-options">
        <h2 class="text-xl font-bold text-gray-900 mb-4"><?php echo esc_html( __( 'Additional Options', 'the-events-calendar-shortcode' ) ); ?></h2>
        <?php echo $injected_options; // phpcs:ignore -- markup produced by trusted hook callbacks ?>
      </div>
      <?php endif; ?>

    </div>

    <div id="tab-styling" class="ecs-tab-panel hidden">

      <?php
      ob_start();
      do_action( 'ecs_admin_page_styling_before' );
      $injected_styling = trim( ob_get_clean() );
      ?>

      <?php if ( $injected_styling ): ?>
      <div class="bg-white rounded-lg shadow-md p-8 mb-6 ecs-injected-options">
        <?php echo $injected_styling; // phpcs:ignore -- markup produced by trusted hook callbacks ?>
      </div>
      <?php endif; ?>

      <div class="bg-white rounded-lg shadow-md p-8 mb-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4"><?php echo esc_html( __( 'CSS Classes Reference', 'the-events-calendar-shortcode' ) ); ?></h2>
        <p class="text-gray-600 text-base mb-4"><?php echo esc_html( __( 'By default the plugin does not include styling. Events are listed with these CSS classes for custom styling:', 'the-events-calendar-shortcode' ) ); ?></p>

        <table class="w-full text-sm text-left mb-6">
          <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
            <tr>
              <th class="px-6 py-3 font-medium"><?php echo esc_html( __( 'Element', 'the-events-calendar-shortcode' ) ); ?></th>
              <th class="px-6 py-3 font-medium"><?php echo esc_html( __( 'Selector', 'the-events-calendar-shortcode' ) ); ?></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-3 text-gray-600"><?php echo esc_html( __( 'Event list', 'the-events-calendar-shortcode' ) ); ?></td>
              <td class="px-6 py-3"><code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1">ul.ecs-event-list</code></td>
            </tr>
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-3 text-gray-600"><?php echo esc_html( __( 'Event item', 'the-events-calendar-shortcode' ) ); ?></td>
              <td class="px-6 py-3"><code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1">li.ecs-event</code></td>
            </tr>
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-3 text-gray-600"><?php echo esc_html( __( 'Featured event', 'the-events-calendar-shortcode' ) ); ?></td>
              <td class="px-6 py-3"><code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1">li.ecs-featured-event</code></td>
            </tr>
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-3 text-gray-600"><?php echo esc_html( __( 'Event title', 'the-events-calendar-shortcode' ) ); ?></td>
              <td class="px-6 py-3"><code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1">h4.entry-title.summary</code></td>
            </tr>
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-3 text-gray-600"><?php echo esc_html( __( 'Date', 'the-events-calendar-shortcode' ) ); ?></td>
              <td class="px-6 py-3"><code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1">time</code></td>
            </tr>
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-3 text-gray-600"><?php echo esc_html( __( 'Venue', 'the-events-calendar-shortcode' ) ); ?></td>
              <td class="px-6 py-3"><code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1">.venue</code></td>
            </tr>
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-3 text-gray-600"><?php echo esc_html( __( 'View all events', 'the-events-calendar-shortcode' ) ); ?></td>
              <td class="px-6 py-3"><code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1">span.ecs-all-events</code></td>
            </tr>
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-3 text-gray-600"><?php echo esc_html( __( 'Excerpt', 'the-events-calendar-shortcode' ) ); ?></td>
              <td class="px-6 py-3"><code class="text-xs bg-gray-100 text-gray-800 rounded px-2 py-1">p.ecs-excerpt</code></td>
            </tr>
          </tbody>
        </table>

        <a class="inline-block rounded-lg px-6 py-3 shadow-md text-center no-underline font-medium text-sm text-gray-700 bg-gray-100 hover:text-gray-700 border border-gray-200" href="https://eventcalendarnewsletter.com/getting-started/?<?php echo esc_attr( $utm_base ); ?>&utm_content=top#css" target="_blank"><?php echo esc_html( __( 'More Information on Customizing the Style', 'the-events-calendar-shortcode' ) ); ?></a>
      </div>

    </div>

    <?php if ( $show_upgrades ): ?>
    <div id="ecs-link-display" class="bg-white rounded-lg shadow-md p-6 mb-6">
      <?php wp_nonce_field( 'ecs-link-nonce', 'ecs-link-nonce' ); ?>
      <h3 class="text-base font-bold text-gray-900 mb-2"><?php echo esc_html__( 'We hope this plugin is helping you out!', 'the-events-calendar-shortcode' ); ?></h3>
      <p class="text-gray-600 text-sm mb-3"><?php echo esc_html__( 'Would you like to show your thanks? Add a small link underneath your events pointing to The Events Calendar Shortcode project.', 'the-events-calendar-shortcode' ); ?></p>
      <label class="text-sm text-gray-700 cursor-pointer"><input type="checkbox" value="1" id="show-ecs-link"<?php echo get_option( 'ecs-show-link' ) ? ' checked' : ''; ?> class="mr-2"><?php echo esc_html__( 'Show small link to The Events Calendar Shortcode', 'the-events-calendar-shortcode' ); ?></label>
      <div class="text-sm text-green-600 mt-2 toggle-message" style="display:none;"><?php echo esc_html__( 'Value saved', 'the-events-calendar-shortcode' ); ?></div>
    </div>
    <?php endif; ?>

    <div class="text-center py-4">
      <p class="text-gray-400 text-xs"><?php echo sprintf( esc_html__( 'This plugin is not developed by or affiliated with The Events Calendar or %s in any way.', 'the-events-calendar-shortcode' ), 'Modern Tribe' ); ?></p>
    </div>

  </div>
</div>
