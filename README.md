# YOGA — YouTube Opportunity & Growth Analyzer

YOGA is a free and open-source WordPress plugin maintained by Best YouTube Views. It analyzes public YouTube videos and channels, highlights practical growth opportunities, checks public promotion-readiness signals, and produces a structured report.

## Source code

The complete WordPress plugin source for version 0.1.2 is available in [`yoga-analyzer/`](yoga-analyzer/):

```text
yoga-analyzer/
├── yoga-analyzer.php
├── readme.txt
├── includes/
│   └── class-yoga-analyzer.php
└── assets/
    ├── css/yoga.css
    └── js/yoga.js
```

The source does not contain production credentials. A YouTube Data API v3 key is supplied by each site administrator through the YOGA settings page or as `YOGA_YOUTUBE_API_KEY` in `wp-config.php`.

## Installation

1. Download `yoga-analyzer-0.1.2.zip` from this repository.
2. In WordPress, open **Plugins → Add New → Upload Plugin**.
3. Upload the ZIP and activate **YOGA — YouTube Opportunity & Growth Analyzer**.
4. Open **Settings → YOGA Analyzer** and add a YouTube Data API v3 key.
5. Add the shortcode `[yoga_analyzer]` to a page.

## Live tool

https://bestyoutubeviews.com/free-youtube-analyzer-tool/

## GitHub Pages overview

https://viralservice.github.io/youtube-opportunity-growth-analyzer/

## Features

- Public YouTube video and channel metadata analysis
- Title, thumbnail, engagement, embed and restriction checks
- Promotion and Google Ads readiness signals
- Growth opportunity scoring and prioritized action plan
- Email unlock and private report links
- JSON and CSV export
- Configurable rate limiting and API response caching

## Data and privacy

YOGA communicates server-side with the YouTube Data API. It can store analysis reports, email addresses submitted to unlock reports, optional marketing consent, and a hashed IP value used for rate limiting. Site operators are responsible for configuring their privacy disclosures and retention practices.

## Maintainer

Best YouTube Views  
https://bestyoutubeviews.com/  
Contact: info@bestyoutubeviews.com

## License

MIT License — Copyright (c) 2026 Best YouTube Views. See [`LICENSE`](LICENSE).
