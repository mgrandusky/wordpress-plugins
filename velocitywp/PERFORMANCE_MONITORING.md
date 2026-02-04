# Performance Monitoring & Analytics

## Overview

The Performance Monitoring feature provides comprehensive real-user monitoring (RUM) and analytics for your WordPress site. It tracks Core Web Vitals and server-side performance metrics to give you actionable insights into your site's actual performance.

## Features

### 1. Core Web Vitals Tracking

Track the metrics that Google uses to measure user experience:

- **LCP (Largest Contentful Paint)**: Measures loading performance
  - Good: ≤ 2.5s
  - Needs Improvement: 2.5s - 4.0s
  - Poor: > 4.0s

- **FID (First Input Delay)**: Measures interactivity
  - Good: ≤ 100ms
  - Needs Improvement: 100ms - 300ms
  - Poor: > 300ms

- **CLS (Cumulative Layout Shift)**: Measures visual stability
  - Good: ≤ 0.1
  - Needs Improvement: 0.1 - 0.25
  - Poor: > 0.25

- **TTFB (Time to First Byte)**: Measures server response time
  - Good: ≤ 800ms
  - Needs Improvement: 800ms - 1800ms
  - Poor: > 1800ms

- **FCP (First Contentful Paint)**: Measures when content first appears

### 2. Real User Monitoring (RUM)

Collects performance data from actual visitors using:
- Browser PerformanceObserver API
- Beacon API for reliable data transmission
- Device detection (mobile vs desktop)
- Connection type tracking
- Configurable sample rate

### 3. Server-Side Metrics

Tracks backend performance:
- Page generation time
- Database query count
- Memory usage
- Query execution time

### 4. Analytics Dashboard

Beautiful, comprehensive dashboard showing:
- **Score Cards**: Color-coded Core Web Vitals with ratings
- **Overall Score**: 0-100 performance score
- **Trend Charts**: Visual representation of metrics over time
- **Device Breakdown**: Mobile vs Desktop performance comparison
- **Slowest Pages**: Identify problematic pages
- **Quick Stats**: At-a-glance performance metrics

### 5. Smart Recommendations

Automated suggestions based on your metrics:
- LCP optimization tips
- FID improvement strategies
- CLS fixes
- Database optimization recommendations
- Customized based on your actual data

## Configuration

### Basic Settings

1. **Enable Performance Monitoring**
   - Master switch to activate/deactivate the feature

2. **Track Real User Metrics (RUM)**
   - Enable client-side performance tracking
   - Collects Core Web Vitals from real visitors

3. **Track Server Performance**
   - Enable server-side metrics collection
   - Monitors generation time, queries, memory

4. **Debug Comments**
   - Add performance info as HTML comments
   - Useful for development and troubleshooting

### Data Management

1. **Data Retention**
   - Default: 30 days
   - Range: 1-365 days
   - Older data automatically deleted

2. **Sample Rate**
   - Default: 100% (track all pageviews)
   - Reduce to save database space
   - Example: 50% = track half of pageviews

3. **Manual Cleanup**
   - Button to immediately remove old data
   - Respects retention period setting

## Usage

### Viewing Performance Data

1. Navigate to **Settings → WP Speed Booster**
2. Click the **Performance Monitor** tab
3. Select time period (24h, 7d, 30d, 90d)
4. Review:
   - Core Web Vitals scores
   - Performance trends
   - Device breakdown
   - Slowest pages
   - Recommendations

### Interpreting Scores

**Performance Score (0-100)**:
- 80-100: Good ✅
- 50-79: Needs Improvement ⚠️
- 0-49: Poor ❌

**Color Coding**:
- Green: Good
- Orange: Needs Improvement
- Red: Poor

### Exporting Data

1. Scroll to **Export Data** section
2. Choose format:
   - **CSV**: Spreadsheet-compatible
   - **JSON**: Developer-friendly
3. Click export button
4. File downloads automatically

## Database Structure

Performance data stored in `{prefix}_wpspeed_performance` table:

```sql
- id: Unique identifier
- url: Page URL
- lcp, fid, cls, ttfb, fcp: Core Web Vitals
- dom_load, window_load: Load times
- resource_count, total_size: Resource metrics
- device: mobile/desktop
- connection: Connection type
- generation_time: Server generation time
- query_count: Database queries
- memory_used: Memory consumption
- timestamp: When recorded
```

## Cron Jobs

Automatic maintenance via WordPress cron:

1. **Daily Cleanup** (`wpspeed_cleanup_performance_data`)
   - Removes data older than retention period
   - Runs once per day
   - Keeps database size manageable

## Privacy & Performance

### Privacy-Compliant
- No personally identifiable information (PII) collected
- Only anonymous performance metrics
- No user tracking or cookies
- GDPR/CCPA compliant

### Performance Impact
- Minimal client-side overhead (~2KB JS)
- Beacon API for non-blocking data transmission
- Sample rate control reduces database load
- Indexed database queries for fast analytics

## Troubleshooting

### No Data Appearing

1. **Check if enabled**: Verify "Enable Performance Monitoring" is checked
2. **Check RUM**: Ensure "Track Real User Metrics" is enabled
3. **Visit site**: Performance data comes from real pageviews
4. **Wait**: Data collection needs actual traffic
5. **Check browser**: PerformanceObserver required (modern browsers)

### High Database Size

1. **Reduce retention**: Lower data retention days
2. **Reduce sample rate**: Track smaller percentage of pageviews
3. **Run cleanup**: Use manual cleanup button
4. **Check cron**: Ensure WordPress cron is running

### Inaccurate Metrics

1. **Check sample size**: Need enough data for accuracy
2. **Verify period**: Check time period selector
3. **Browser cache**: Clear cache and test fresh
4. **Multiple sources**: Check both mobile and desktop

## Best Practices

1. **Monitor regularly**: Check dashboard weekly
2. **Act on recommendations**: Follow suggested optimizations
3. **Track improvements**: Compare before/after changes
4. **Balance sample rate**: 100% for small sites, lower for large
5. **Maintain retention**: Keep enough history for trends
6. **Export regularly**: Backup important data
7. **Share insights**: Use data for stakeholder reports

## Technical Details

### Client-Side Collection

JavaScript injected in footer:
```javascript
- Uses PerformanceObserver API
- Collects LCP, FID, CLS automatically
- Uses Navigation Timing API for load metrics
- Sends via Beacon API on page unload
```

### AJAX Endpoints

- `wpspeed_track_performance`: Store RUM data
- `wpspeed_get_analytics`: Fetch analytics
- `wpspeed_export_data`: Export performance data
- `wpspeed_cleanup_data`: Manual cleanup

### Hooks & Filters

**Actions**:
- `init`: Start server-side tracking
- `wp_footer`: Inject RUM script, end tracking
- `admin_footer`: End tracking for admin pages
- `wpspeed_cleanup_performance_data`: Scheduled cleanup

## Support

For issues or questions:
1. Check this documentation
2. Review plugin settings
3. Check browser console for JS errors
4. Verify WordPress cron is working
5. Contact plugin support with:
   - WordPress version
   - PHP version
   - Browser used
   - Error messages (if any)

## Changelog

### Version 1.0.0
- Initial release
- Core Web Vitals tracking
- RUM implementation
- Server-side metrics
- Analytics dashboard
- Export functionality
- Smart recommendations
