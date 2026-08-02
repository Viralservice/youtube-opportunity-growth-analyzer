<?php

if (!defined('ABSPATH')) {
    exit;
}

final class YOGA_Analyzer {
    private static $instance = null;
    private $table_name;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'yoga_reports';

        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_shortcode('yoga_analyzer', array($this, 'render_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public static function activate() {
        global $wpdb;
        $table = $wpdb->prefix . 'yoga_reports';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            token varchar(64) NOT NULL,
            video_id varchar(20) NOT NULL,
            email varchar(190) DEFAULT NULL,
            marketing_consent tinyint(1) NOT NULL DEFAULT 0,
            preview_json longtext NOT NULL,
            report_json longtext NOT NULL,
            ip_hash varchar(64) DEFAULT NULL,
            created_at datetime NOT NULL,
            unlocked_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY token (token),
            KEY video_id (video_id),
            KEY email (email)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        add_option('yoga_daily_limit', 20);
        add_option('yoga_cache_hours', 6);
        add_option('yoga_professional_analysis_url', home_url('/youtube-video-channel-analysis/'));
    }

    public function admin_menu() {
        add_options_page(
            'YOGA Analyzer',
            'YOGA Analyzer',
            'manage_options',
            'yoga-analyzer',
            array($this, 'settings_page')
        );
    }

    public function register_settings() {
        register_setting('yoga_analyzer_settings', 'yoga_youtube_api_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ));
        register_setting('yoga_analyzer_settings', 'yoga_daily_limit', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 20,
        ));
        register_setting('yoga_analyzer_settings', 'yoga_cache_hours', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 6,
        ));
        register_setting('yoga_analyzer_settings', 'yoga_professional_analysis_url', array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => home_url('/youtube-video-channel-analysis/'),
        ));
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>YOGA — YouTube Opportunity &amp; Growth Analyzer</h1>
            <p>Inserisci lo shortcode <code>[yoga_analyzer]</code> nella pagina scelta.</p>
            <form method="post" action="options.php">
                <?php settings_fields('yoga_analyzer_settings'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="yoga_youtube_api_key">YouTube Data API v3 key</label></th>
                        <td>
                            <input type="password" class="regular-text" id="yoga_youtube_api_key" name="yoga_youtube_api_key" value="<?php echo esc_attr(get_option('yoga_youtube_api_key', '')); ?>" autocomplete="off">
                            <p class="description">Puoi anche definire <code>YOGA_YOUTUBE_API_KEY</code> in wp-config.php. La costante ha priorità.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="yoga_daily_limit">Analisi anonime per 24 ore</label></th>
                        <td><input type="number" min="1" max="500" id="yoga_daily_limit" name="yoga_daily_limit" value="<?php echo esc_attr(get_option('yoga_daily_limit', 20)); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="yoga_cache_hours">Durata cache (ore)</label></th>
                        <td><input type="number" min="1" max="168" id="yoga_cache_hours" name="yoga_cache_hours" value="<?php echo esc_attr(get_option('yoga_cache_hours', 6)); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="yoga_professional_analysis_url">Professional analysis URL</label></th>
                        <td><input type="url" class="regular-text" id="yoga_professional_analysis_url" name="yoga_professional_analysis_url" value="<?php echo esc_attr(get_option('yoga_professional_analysis_url', home_url('/youtube-video-channel-analysis/'))); ?>"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_shortcode() {
        wp_enqueue_style(
            'yoga-analyzer',
            YOGA_ANALYZER_URL . 'assets/css/yoga.css',
            array(),
            YOGA_ANALYZER_VERSION
        );
        wp_enqueue_script(
            'yoga-analyzer',
            YOGA_ANALYZER_URL . 'assets/js/yoga.js',
            array(),
            YOGA_ANALYZER_VERSION,
            true
        );
        wp_localize_script('yoga-analyzer', 'YOGA_CONFIG', array(
            'restUrl' => esc_url_raw(rest_url('yoga/v1/')),
            'siteName' => get_bloginfo('name'),
            'professionalUrl' => esc_url_raw(get_option('yoga_professional_analysis_url', home_url('/youtube-video-channel-analysis/'))),
            'pageUrl' => esc_url_raw($this->current_url()),
        ));

        ob_start();
        ?>
        <section class="yoga-app" data-yoga-app>
            <div class="yoga-shell">
                <div class="yoga-intro">
                    <p class="yoga-kicker">Free YouTube Tool by Best YouTube Views</p>
                    <p class="yoga-intro-title"><strong>YOGA — YouTube Opportunity &amp; Growth Analyzer</strong></p>
                    <p>Turn one YouTube link into clear growth opportunities, technical checks and practical next steps.</p>
                </div>

                <div class="yoga-mode-grid" role="tablist" aria-label="Analysis mode">
                    <button type="button" class="yoga-mode is-active" data-yoga-mode="post" role="tab" aria-selected="true">
                        <span><strong>Analyze a published video</strong><small>Paste the link. YOGA does the rest.</small></span>
                    </button>
                    <button type="button" class="yoga-mode" data-yoga-mode="pre" role="tab" aria-selected="false">
                        <span><strong>Prepare a new video</strong><small>Lightweight pre-publish check — next build.</small></span>
                    </button>
                </div>

                <div class="yoga-panel yoga-input-panel" data-yoga-post-panel>
                    <form data-yoga-form novalidate>
                        <label for="yoga-video-url">YouTube video URL</label>
                        <div class="yoga-url-row">
                            <input id="yoga-video-url" name="video_url" type="text" inputmode="url" autocomplete="url" placeholder="https://www.youtube.com/watch?v=…" required>
                            <button type="submit" class="button yoga-primary-button">Analyze my video</button>
                        </div>
                        <p class="yoga-help">Works with standard videos, Shorts, youtu.be links, live replays and direct video IDs.</p>
                    </form>
                </div>

                <div class="yoga-panel yoga-pre-panel" data-yoga-pre-panel hidden>
                    <div class="yoga-coming-soon">
                        <span aria-hidden="true">✦</span>
                        <div>
                            <h2>Pre-Publish is the next module</h2>
                            <p>It will require only an unlisted link, or a provisional title and thumbnail. No long questionnaire.</p>
                        </div>
                    </div>
                </div>

                <div class="yoga-status" data-yoga-status hidden aria-live="polite">
                    <div class="yoga-spinner" aria-hidden="true"></div>
                    <div>
                        <strong data-yoga-status-title>Reading the video…</strong>
                        <span data-yoga-status-text>Checking public data and technical availability.</span>
                    </div>
                </div>

                <div class="yoga-error" data-yoga-error hidden role="alert"></div>
                <div class="yoga-results" data-yoga-results hidden></div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    public function register_rest_routes() {
        register_rest_route('yoga/v1', '/analyze', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_analyze'),
            'permission_callback' => '__return_true',
        ));
        register_rest_route('yoga/v1', '/unlock', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_unlock'),
            'permission_callback' => '__return_true',
        ));
        register_rest_route('yoga/v1', '/report/(?P<token>[A-Za-z0-9_-]{32,64})', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_report'),
            'permission_callback' => '__return_true',
        ));
    }

    public function rest_analyze(WP_REST_Request $request) {
        $params = $request->get_json_params();
        $raw = isset($params['url']) ? trim((string) $params['url']) : '';
        $video_id = $this->extract_video_id($raw);

        if (!$video_id) {
            return new WP_Error('invalid_video_url', 'Please enter a valid YouTube video URL or 11-character video ID.', array('status' => 400));
        }

        $api_key = $this->api_key();
        if (!$api_key) {
            return new WP_Error('missing_api_key', 'YOGA is not configured yet. Add a YouTube Data API v3 key in Settings → YOGA Analyzer.', array('status' => 503));
        }

        $rate = $this->consume_rate_limit();
        if (is_wp_error($rate)) {
            return $rate;
        }

        $cache_key = 'yoga_analysis_' . $video_id;
        $report = get_transient($cache_key);
        if (!is_array($report)) {
            $report = $this->build_report($video_id, $api_key);
            if (is_wp_error($report)) {
                return $report;
            }
            $hours = max(1, absint(get_option('yoga_cache_hours', 6)));
            set_transient($cache_key, $report, $hours * HOUR_IN_SECONDS);
        }

        $preview = $this->build_preview($report);
        $token = wp_generate_password(48, false, false);

        global $wpdb;
        $inserted = $wpdb->insert(
            $this->table_name,
            array(
                'token' => $token,
                'video_id' => $video_id,
                'preview_json' => wp_json_encode($preview),
                'report_json' => wp_json_encode($report),
                'ip_hash' => $this->ip_hash(),
                'created_at' => current_time('mysql', true),
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );

        if (!$inserted) {
            return new WP_Error('save_failed', 'The analysis was created but could not be saved. Please try again.', array('status' => 500));
        }

        return rest_ensure_response(array(
            'token' => $token,
            'preview' => $preview,
        ));
    }

    public function rest_unlock(WP_REST_Request $request) {
        $params = $request->get_json_params();
        $token = isset($params['token']) ? sanitize_text_field($params['token']) : '';
        $email = isset($params['email']) ? sanitize_email($params['email']) : '';
        $marketing = !empty($params['marketing']) ? 1 : 0;
        $page_url = isset($params['pageUrl']) ? esc_url_raw($params['pageUrl']) : home_url('/');

        if (!$token || !is_email($email)) {
            return new WP_Error('invalid_unlock', 'Enter a valid email address to unlock the complete action plan.', array('status' => 400));
        }

        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE token = %s LIMIT 1", $token), ARRAY_A);
        if (!$row) {
            return new WP_Error('report_not_found', 'This report is no longer available. Run the analysis again.', array('status' => 404));
        }

        $wpdb->update(
            $this->table_name,
            array(
                'email' => $email,
                'marketing_consent' => $marketing,
                'unlocked_at' => current_time('mysql', true),
            ),
            array('id' => (int) $row['id']),
            array('%s', '%d', '%s'),
            array('%d')
        );

        $report = json_decode($row['report_json'], true);
        $this->send_report_email($email, $token, $report, $page_url);

        return rest_ensure_response(array(
            'report' => $report,
            'shareUrl' => add_query_arg('yoga_report', rawurlencode($token), $page_url),
        ));
    }

    public function rest_report(WP_REST_Request $request) {
        $token = sanitize_text_field($request['token']);
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT email, report_json FROM {$this->table_name} WHERE token = %s LIMIT 1", $token), ARRAY_A);

        if (!$row || empty($row['email'])) {
            return new WP_Error('report_not_found', 'This report is unavailable or has not been unlocked.', array('status' => 404));
        }

        return rest_ensure_response(array('report' => json_decode($row['report_json'], true)));
    }

    private function build_report($video_id, $api_key) {
        $video_response = $this->youtube_get('videos', array(
            'part' => 'snippet,contentDetails,status,statistics,player,topicDetails',
            'id' => $video_id,
        ), $api_key);

        if (is_wp_error($video_response)) {
            return $video_response;
        }
        if (empty($video_response['items'][0])) {
            return new WP_Error('video_not_found', 'The video could not be found or is not publicly accessible.', array('status' => 404));
        }

        $video = $video_response['items'][0];
        $channel_id = $video['snippet']['channelId'] ?? '';

        $channel_response = $this->youtube_get('channels', array(
            'part' => 'snippet,contentDetails,statistics,brandingSettings',
            'id' => $channel_id,
        ), $api_key);
        if (is_wp_error($channel_response)) {
            return $channel_response;
        }
        $channel = $channel_response['items'][0] ?? array();

        $recent_videos = array();
        $uploads_id = $channel['contentDetails']['relatedPlaylists']['uploads'] ?? '';
        if ($uploads_id) {
            $playlist = $this->youtube_get('playlistItems', array(
                'part' => 'contentDetails',
                'playlistId' => $uploads_id,
                'maxResults' => 20,
            ), $api_key);
            if (!is_wp_error($playlist) && !empty($playlist['items'])) {
                $ids = array();
                foreach ($playlist['items'] as $item) {
                    $id = $item['contentDetails']['videoId'] ?? '';
                    if ($id && $id !== $video_id) {
                        $ids[] = $id;
                    }
                }
                if ($ids) {
                    $recent_response = $this->youtube_get('videos', array(
                        'part' => 'snippet,contentDetails,statistics,status',
                        'id' => implode(',', array_slice($ids, 0, 19)),
                    ), $api_key);
                    if (!is_wp_error($recent_response)) {
                        $recent_videos = $recent_response['items'] ?? array();
                    }
                }
            }
        }

        $comments = array();
        $comments_enabled = true;
        // Call this endpoint even when commentCount is zero: an empty enabled section and
        // a disabled comment section are two different technical states.
        $comments_response = $this->youtube_get('commentThreads', array(
            'part' => 'snippet',
            'videoId' => $video_id,
            'maxResults' => 30,
            'order' => 'relevance',
            'textFormat' => 'plainText',
        ), $api_key, true);
        if (is_wp_error($comments_response)) {
            if ('commentsDisabled' === $comments_response->get_error_code()) {
                $comments_enabled = false;
            }
        } else {
            foreach (($comments_response['items'] ?? array()) as $thread) {
                $text = $thread['snippet']['topLevelComment']['snippet']['textDisplay'] ?? '';
                if ($text) {
                    $comments[] = wp_strip_all_tags($text);
                }
            }
        }

        return $this->analyze_public_data($video, $channel, $recent_videos, $comments, $comments_enabled);
    }

    private function analyze_public_data(array $video, array $channel, array $recent_videos, array $comments, $comments_enabled) {
        $snippet = $video['snippet'] ?? array();
        $details = $video['contentDetails'] ?? array();
        $status = $video['status'] ?? array();
        $stats = $video['statistics'] ?? array();
        $rating = $details['contentRating'] ?? array();
        $regions = $details['regionRestriction'] ?? array();

        $title = (string) ($snippet['title'] ?? '');
        $description = (string) ($snippet['description'] ?? '');
        $published = (string) ($snippet['publishedAt'] ?? '');
        $views = (int) ($stats['viewCount'] ?? 0);
        $likes = (int) ($stats['likeCount'] ?? 0);
        $comment_count = (int) ($stats['commentCount'] ?? 0);
        $subscribers = (int) ($channel['statistics']['subscriberCount'] ?? 0);
        $hidden_subscribers = !empty($channel['statistics']['hiddenSubscriberCount']);
        $age_days = max(1, (int) floor((time() - strtotime($published)) / DAY_IN_SECONDS));
        $duration_seconds = $this->iso_duration_seconds($details['duration'] ?? 'PT0S');
        $video_type = $this->video_type($duration_seconds, $snippet, $details);

        $recent_metrics = array();
        foreach ($recent_videos as $recent) {
            $rp = $recent['snippet']['publishedAt'] ?? '';
            $rv = (int) ($recent['statistics']['viewCount'] ?? 0);
            if (!$rp || $rv < 1) {
                continue;
            }
            $rdays = max(1, (int) floor((time() - strtotime($rp)) / DAY_IN_SECONDS));
            $recent_metrics[] = array(
                'id' => $recent['id'] ?? '',
                'title' => $recent['snippet']['title'] ?? '',
                'views' => $rv,
                'age_days' => $rdays,
                'views_per_day' => round($rv / $rdays, 2),
            );
        }

        $recent_vpd = array_column($recent_metrics, 'views_per_day');
        $median_vpd = $this->median($recent_vpd);
        $views_per_day = round($views / $age_days, 2);
        $velocity_index = $median_vpd > 0 ? round($views_per_day / $median_vpd, 2) : null;
        $like_rate = $views > 0 ? round(($likes / $views) * 1000, 2) : 0;
        $comment_rate = $views > 0 ? round(($comment_count / $views) * 1000, 2) : 0;
        $view_sub_ratio = (!$hidden_subscribers && $subscribers > 0) ? round($views / $subscribers, 3) : null;

        $blocked = isset($regions['blocked']) && is_array($regions['blocked']) ? array_values($regions['blocked']) : array();
        $allowed = isset($regions['allowed']) && is_array($regions['allowed']) ? array_values($regions['allowed']) : array();
        if (array_key_exists('allowed', $regions)) {
            $global_reach = empty($allowed) ? 'Blocked in all countries' : 'Available only in selected countries';
        } elseif (!empty($blocked)) {
            $global_reach = 'Available worldwide except blocked countries';
        } else {
            $global_reach = 'Available worldwide';
        }

        $age_restricted = (($rating['ytRating'] ?? '') === 'ytAgeRestricted');
        $embeddable = !empty($status['embeddable']);
        $made_for_kids = array_key_exists('madeForKids', $status) ? (bool) $status['madeForKids'] : null;
        $captions = (($details['caption'] ?? 'false') === 'true');
        $chapters = preg_match_all('/^(?:\d{1,2}:)?\d{1,2}:\d{2}\s+.+$/m', $description, $chapter_matches);
        $hashtags = preg_match_all('/#[\p{L}\p{N}_-]+/u', $description, $hashtag_matches);
        $links = preg_match_all('~https?://[^\s]+~i', $description, $link_matches);
        $has_cta = (bool) preg_match('/subscribe|iscriv|follow|comment|leave a comment|visit|download|stream|listen|watch next|scopri|seguimi|link in bio/i', $description);
        $tags = isset($snippet['tags']) && is_array($snippet['tags']) ? $snippet['tags'] : array();
        $thumb = $this->best_thumbnail($snippet['thumbnails'] ?? array());

        $discoverability = array(
            'title_length' => mb_strlen($title),
            'title_mobile_preview' => mb_strlen($title) > 62 ? mb_substr($title, 0, 59) . '…' : $title,
            'description_length' => mb_strlen($description),
            'tag_count' => count($tags),
            'hashtags_count' => (int) $hashtags,
            'chapters_count' => (int) $chapters,
            'links_count' => (int) $links,
            'has_call_to_action' => $has_cta,
            'primary_terms' => $this->extract_terms($title . ' ' . mb_substr($description, 0, 800)),
        );

        $audience = $this->analyze_comments($comments);

        $context = array(
            'views_per_day' => $views_per_day,
            'median_recent_views_per_day' => $median_vpd,
            'velocity_index' => $velocity_index,
            'momentum' => $this->momentum_label($velocity_index),
            'like_rate_per_1000_views' => $like_rate,
            'comment_rate_per_1000_views' => $comment_rate,
            'views_to_subscribers_ratio' => $view_sub_ratio,
            'recent_videos_analyzed' => count($recent_metrics),
            'recent_videos' => array_slice($recent_metrics, 0, 10),
        );

        $snapshot = array(
            'video_id' => $video['id'] ?? '',
            'title' => $title,
            'description' => $description,
            'channel_id' => $snippet['channelId'] ?? '',
            'channel_title' => $snippet['channelTitle'] ?? ($channel['snippet']['title'] ?? ''),
            'published_at' => $published,
            'age_days' => $age_days,
            'duration_seconds' => $duration_seconds,
            'duration' => $this->format_duration($duration_seconds),
            'video_type' => $video_type,
            'views' => $views,
            'likes' => $likes,
            'comments' => $comment_count,
            'subscribers' => $hidden_subscribers ? null : $subscribers,
            'subscriber_count_hidden' => $hidden_subscribers,
            'thumbnail' => $thumb,
            'category_id' => $snippet['categoryId'] ?? '',
            'default_language' => $snippet['defaultLanguage'] ?? ($snippet['defaultAudioLanguage'] ?? ''),
            'tags' => $tags,
        );

        $access = array(
            'embeddable' => $embeddable,
            'website_compatibility' => $embeddable ? 'Ready' : 'Limited',
            'privacy_status' => $status['privacyStatus'] ?? 'unknown',
            'license' => $status['license'] ?? 'youtube',
            'made_for_kids' => $made_for_kids,
            'age_restricted' => $age_restricted,
            'captions_available' => $captions,
            'definition' => strtoupper($details['definition'] ?? ''),
            'dimension' => $details['dimension'] ?? '',
            'projection' => $details['projection'] ?? '',
            'licensed_content' => !empty($details['licensedContent']),
            'global_reach' => $global_reach,
            'blocked_countries' => $blocked,
            'allowed_countries' => $allowed,
            'comments_enabled' => (bool) $comments_enabled,
            'player_embed_html' => $embeddable ? ($video['player']['embedHtml'] ?? '') : '',
        );

        $strengths = $this->build_strengths($snapshot, $access, $context, $discoverability);
        $actions = $this->build_actions($snapshot, $access, $context, $discoverability, $audience);

        return array(
            'version' => YOGA_ANALYZER_VERSION,
            'generated_at' => gmdate('c'),
            'snapshot' => $snapshot,
            'accessibility' => $access,
            'performance' => $context,
            'discoverability' => $discoverability,
            'audience_signals' => $audience,
            'strengths' => $strengths,
            'actions' => $actions,
            'disclaimer' => 'YOGA uses public YouTube data. Private YouTube Studio metrics such as impressions, CTR, retention, watch time and monetization status are not inferred as facts.',
        );
    }

    private function build_preview(array $report) {
        $actions = $report['actions'] ?? array();
        return array(
            'snapshot' => array(
                'title' => $report['snapshot']['title'] ?? '',
                'channel_title' => $report['snapshot']['channel_title'] ?? '',
                'thumbnail' => $report['snapshot']['thumbnail'] ?? '',
                'views' => $report['snapshot']['views'] ?? 0,
                'video_type' => $report['snapshot']['video_type'] ?? '',
            ),
            'accessibility' => array(
                'website_compatibility' => $report['accessibility']['website_compatibility'] ?? 'Unknown',
                'embeddable' => $report['accessibility']['embeddable'] ?? false,
                'global_reach' => $report['accessibility']['global_reach'] ?? 'Unknown',
                'age_restricted' => $report['accessibility']['age_restricted'] ?? false,
            ),
            'strengths' => array_slice($report['strengths'] ?? array(), 0, 2),
            'top_opportunity' => $actions[0] ?? null,
            'action_count' => count($actions),
        );
    }

    private function build_strengths(array $snapshot, array $access, array $context, array $discoverability) {
        $items = array();
        if ($access['embeddable']) {
            $items[] = array('title' => 'Ready for external websites', 'text' => 'The video can be embedded and played outside YouTube.');
        }
        if ($access['global_reach'] === 'Available worldwide') {
            $items[] = array('title' => 'Worldwide availability', 'text' => 'No public country restriction is currently reported.');
        }
        if (!$access['age_restricted']) {
            $items[] = array('title' => 'Broad audience access', 'text' => 'No public YouTube age restriction is reported.');
        }
        if (($context['velocity_index'] ?? 0) >= 1.2) {
            $items[] = array('title' => 'Positive public momentum', 'text' => 'Daily views are currently above the recent channel baseline.');
        }
        if (($context['like_rate_per_1000_views'] ?? 0) >= 20) {
            $items[] = array('title' => 'Active audience response', 'text' => 'The public like rate is a useful positive signal.');
        }
        if (($discoverability['title_length'] ?? 0) >= 35 && ($discoverability['title_length'] ?? 0) <= 65) {
            $items[] = array('title' => 'Readable title length', 'text' => 'The title fits a practical range for most YouTube surfaces.');
        }
        if (($discoverability['description_length'] ?? 0) >= 250) {
            $items[] = array('title' => 'Useful description depth', 'text' => 'The description gives YouTube and viewers meaningful context.');
        }
        if (count($items) < 2) {
            $items[] = array('title' => 'A clear starting point', 'text' => 'YOGA found enough public information to build a practical action plan.');
        }
        return array_slice($items, 0, 5);
    }

    private function build_actions(array $snapshot, array $access, array $context, array $discoverability, array $audience) {
        $actions = array();

        if (!$access['embeddable']) {
            $actions[] = $this->action('Enable video embedding', 'Allow the video to play on external websites and compatible promotional placements.', 'Do now', 'High', 'Easy', 'Open YouTube Studio → Content → video details → Show more → Allow embedding.', 'accessibility');
        }
        if (!empty($access['blocked_countries']) || !empty($access['allowed_countries'])) {
            $actions[] = $this->action('Review country availability', 'The video has public geographic limitations that may reduce reachable audiences or placement compatibility.', 'Do now', 'High', 'Moderate', 'Check copyright and distribution settings in YouTube Studio before promoting the video internationally.', 'accessibility');
        }
        if ($access['age_restricted']) {
            $actions[] = $this->action('Verify the age restriction', 'An age restriction narrows access and can limit discovery, embedding and advertising options.', 'Do now', 'High', 'Moderate', 'Confirm whether the restriction is intentional and review the relevant video details in YouTube Studio.', 'accessibility');
        }
        if (!$access['captions_available']) {
            $actions[] = $this->action('Add accurate captions', 'Captions improve accessibility, comprehension and the amount of textual context available around the video.', 'Do next', 'Medium', 'Moderate', 'Upload or review a subtitle track in YouTube Studio.', 'discoverability');
        }
        if (($discoverability['title_length'] ?? 0) > 70) {
            $actions[] = $this->action('Bring the main idea forward in the title', 'The current title is likely to be truncated on several YouTube surfaces.', 'Do now', 'High', 'Easy', 'Keep the strongest subject or benefit within the first 55–60 characters.', 'packaging');
        } elseif (($discoverability['title_length'] ?? 0) < 28) {
            $actions[] = $this->action('Add useful context to the title', 'A little more specificity can make the promise and topic easier to understand.', 'Do now', 'Medium', 'Easy', 'Add the central benefit, subject or differentiating detail without using filler.', 'packaging');
        }
        if (($discoverability['description_length'] ?? 0) < 150) {
            $actions[] = $this->action('Strengthen the opening description', 'The current description leaves room to explain the video and guide viewers more clearly.', 'Do now', 'Medium', 'Easy', 'Use the first two lines to state the topic, benefit and most relevant next link.', 'discoverability');
        }
        if (($snapshot['duration_seconds'] ?? 0) >= 300 && ($discoverability['chapters_count'] ?? 0) < 2) {
            $actions[] = $this->action('Add video chapters', 'Chapters can help viewers navigate longer content and understand its structure before watching.', 'Do next', 'Medium', 'Easy', 'Add timestamped sections beginning with 00:00 in the description.', 'discoverability');
        }
        if (!$access['comments_enabled']) {
            $actions[] = $this->action('Review comment availability', 'Comments are unavailable, so the video cannot collect visible discussion or a pinned conversation prompt.', 'Consider', 'Medium', 'Easy', 'Enable comments when appropriate for the audience and content settings.', 'audience');
        } elseif (($context['comment_rate_per_1000_views'] ?? 0) < 1.2) {
            $prompt = !empty($audience['questions']) ? 'Answer the most useful recurring question and pin a follow-up prompt.' : 'Pin one specific, easy-to-answer question related to the video.';
            $actions[] = $this->action('Create a stronger conversation prompt', 'There is room to turn more viewers into visible audience interaction.', 'Do next', 'Medium', 'Easy', $prompt, 'audience');
        }
        if (($context['velocity_index'] ?? null) !== null && $context['velocity_index'] < 0.65 && ($context['like_rate_per_1000_views'] ?? 0) >= 12) {
            $actions[] = $this->action('Expand qualified exposure', 'Audience response is encouraging while public view velocity is below the recent channel baseline.', 'Do next', 'High', 'Moderate', 'Distribute the video to relevant communities or consider a targeted YouTube promotion.', 'promotion');
        }
        if (($context['velocity_index'] ?? 0) >= 1.5) {
            $actions[] = $this->action('Protect the current momentum', 'The video is moving faster than the recent channel baseline.', 'Do now', 'High', 'Easy', 'Avoid unnecessary title or thumbnail changes; focus on replies, playlists and relevant distribution.', 'performance');
        }
        if (!($discoverability['has_call_to_action'] ?? false)) {
            $actions[] = $this->action('Add one clear next step', 'The description does not currently show an obvious viewer action.', 'Consider', 'Low', 'Easy', 'Choose one primary action: watch another video, subscribe, visit a page or leave a focused comment.', 'conversion');
        }

        if (empty($actions)) {
            $actions[] = $this->action('Build on the current foundation', 'The public checks do not show an urgent technical limitation.', 'Do next', 'Medium', 'Easy', 'Focus on relevant distribution, viewer replies and a follow-up content opportunity.', 'growth');
        }

        usort($actions, static function ($a, $b) {
            $priority = array('Do now' => 0, 'Do next' => 1, 'Consider' => 2);
            return ($priority[$a['priority']] ?? 9) <=> ($priority[$b['priority']] ?? 9);
        });

        return array_slice($actions, 0, 10);
    }

    private function action($title, $reason, $priority, $impact, $effort, $instruction, $category) {
        return compact('title', 'reason', 'priority', 'impact', 'effort', 'instruction', 'category');
    }

    private function analyze_comments(array $comments) {
        $questions = array();
        $words = array();
        $stop = array('this','that','with','have','your','from','what','when','where','would','could','there','about','video','youtube','really','just','like','good','great','very','more','come','sono','questo','questa','quello','della','delle','degli','anche','molto','video','grazie');

        foreach ($comments as $comment) {
            if (strpos($comment, '?') !== false && count($questions) < 5) {
                $questions[] = mb_substr(trim($comment), 0, 220);
            }
            $tokens = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($comment));
            foreach ($tokens as $token) {
                if (mb_strlen($token) < 5 || in_array($token, $stop, true)) {
                    continue;
                }
                $words[$token] = ($words[$token] ?? 0) + 1;
            }
        }
        arsort($words);

        return array(
            'comments_sampled' => count($comments),
            'questions' => array_values(array_unique($questions)),
            'recurring_terms' => array_slice(array_keys($words), 0, 8),
        );
    }

    private function youtube_get($resource, array $args, $api_key, $allow_comments_error = false) {
        $args['key'] = $api_key;
        $url = add_query_arg($args, 'https://www.googleapis.com/youtube/v3/' . rawurlencode($resource));
        $response = wp_remote_get($url, array(
            'timeout' => 18,
            'headers' => array('Accept' => 'application/json'),
        ));

        if (is_wp_error($response)) {
            return new WP_Error('youtube_connection', 'YOGA could not reach YouTube. Please try again.', array('status' => 502));
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if ($code >= 400) {
            $reason = $body['error']['errors'][0]['reason'] ?? 'youtube_api_error';
            $message = $body['error']['message'] ?? 'YouTube returned an error.';
            if ($allow_comments_error && 'commentsDisabled' === $reason) {
                return new WP_Error('commentsDisabled', $message, array('status' => $code));
            }
            return new WP_Error(sanitize_key($reason), sanitize_text_field($message), array('status' => $code));
        }

        return is_array($body) ? $body : array();
    }

    private function api_key() {
        if (defined('YOGA_YOUTUBE_API_KEY') && YOGA_YOUTUBE_API_KEY) {
            return (string) YOGA_YOUTUBE_API_KEY;
        }
        return trim((string) get_option('yoga_youtube_api_key', ''));
    }

    private function consume_rate_limit() {
        $limit = max(1, absint(get_option('yoga_daily_limit', 20)));
        $key = 'yoga_rate_' . $this->ip_hash();
        $data = get_transient($key);
        if (!is_array($data)) {
            $data = array('count' => 0);
        }
        if ((int) $data['count'] >= $limit) {
            return new WP_Error('rate_limit', 'You have reached today’s free analysis limit. Please try again later.', array('status' => 429));
        }
        $data['count'] = (int) $data['count'] + 1;
        set_transient($key, $data, DAY_IN_SECONDS);
        return true;
    }

    private function ip_hash() {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
        return hash_hmac('sha256', $ip, wp_salt('auth'));
    }

    private function extract_video_id($input) {
        $input = trim((string) $input);
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $input)) {
            return $input;
        }
        $patterns = array(
            '~(?:youtube\.com/(?:watch\?(?:.*&)?v=|shorts/|live/|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})~i',
            '~[?&]v=([A-Za-z0-9_-]{11})~i',
        );
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input, $match)) {
                return $match[1];
            }
        }
        return '';
    }

    private function best_thumbnail(array $thumbnails) {
        foreach (array('maxres', 'standard', 'high', 'medium', 'default') as $size) {
            if (!empty($thumbnails[$size]['url'])) {
                return esc_url_raw($thumbnails[$size]['url']);
            }
        }
        return '';
    }

    private function iso_duration_seconds($duration) {
        try {
            $interval = new DateInterval($duration ?: 'PT0S');
            return ($interval->d * 86400) + ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
        } catch (Exception $e) {
            return 0;
        }
    }

    private function format_duration($seconds) {
        $seconds = max(0, (int) $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remaining = $seconds % 60;
        return $hours > 0 ? sprintf('%d:%02d:%02d', $hours, $minutes, $remaining) : sprintf('%d:%02d', $minutes, $remaining);
    }

    private function video_type($duration_seconds, array $snippet, array $details) {
        $title = mb_strtolower((string) ($snippet['title'] ?? ''));
        if (strpos($title, '#shorts') !== false) {
            return 'Short';
        }
        if ($duration_seconds <= 60) {
            return 'Short-form (≤60 sec)';
        }
        if (!empty($snippet['liveBroadcastContent']) && 'none' !== $snippet['liveBroadcastContent']) {
            return 'Live / scheduled';
        }
        if (($details['projection'] ?? '') === '360') {
            return '360° video';
        }
        return 'Standard video';
    }

    private function median(array $values) {
        $values = array_values(array_filter($values, static function ($value) {
            return is_numeric($value);
        }));
        $count = count($values);
        if (!$count) {
            return 0;
        }
        sort($values, SORT_NUMERIC);
        $middle = intdiv($count, 2);
        if ($count % 2) {
            return round((float) $values[$middle], 2);
        }
        return round(((float) $values[$middle - 1] + (float) $values[$middle]) / 2, 2);
    }

    private function momentum_label($velocity_index) {
        if (null === $velocity_index) {
            return 'Building';
        }
        if ($velocity_index >= 1.5) {
            return 'Strong';
        }
        if ($velocity_index >= 0.85) {
            return 'Promising';
        }
        if ($velocity_index >= 0.45) {
            return 'Emerging';
        }
        return 'Ready for new opportunities';
    }

    private function extract_terms($text) {
        $stopwords = array('this','that','with','from','your','you','the','and','for','how','what','why','when','where','video','official','come','come','della','delle','degli','questo','questa','youtube','per','con','una','uno','che','non','nel','nella','dei','del','gli','all','new');
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text));
        $freq = array();
        foreach ($tokens as $token) {
            if (mb_strlen($token) < 4 || in_array($token, $stopwords, true)) {
                continue;
            }
            $freq[$token] = ($freq[$token] ?? 0) + 1;
        }
        arsort($freq);
        return array_slice(array_keys($freq), 0, 8);
    }

    private function send_report_email($email, $token, array $report, $page_url) {
        $title = $report['snapshot']['title'] ?? 'your YouTube video';
        $share_url = add_query_arg('yoga_report', rawurlencode($token), $page_url ?: home_url('/'));
        $actions = array_slice($report['actions'] ?? array(), 0, 3);
        $lines = array(
            'Your YOGA action plan is ready.',
            '',
            $title,
            '',
        );
        foreach ($actions as $index => $action) {
            $lines[] = ($index + 1) . '. ' . ($action['title'] ?? 'Next opportunity');
            $lines[] = $action['instruction'] ?? '';
            $lines[] = '';
        }
        $lines[] = 'Open your complete report:';
        $lines[] = $share_url;
        $lines[] = '';
        $lines[] = 'YOGA — YouTube Opportunity & Growth Analyzer';
        $lines[] = get_bloginfo('name');

        wp_mail(
            $email,
            'Your YOGA YouTube action plan',
            implode("\n", $lines),
            array('Content-Type: text/plain; charset=UTF-8')
        );
    }

    private function current_url() {
        $scheme = is_ssl() ? 'https://' : 'http://';
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : wp_parse_url(home_url('/'), PHP_URL_HOST);
        $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '/';
        return $scheme . $host . strtok($uri, '?');
    }
}
