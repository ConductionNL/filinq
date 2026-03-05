---
sidebar_position: 10
---

# Reports Interface

The DocuDesk reports interface has been completely redesigned to provide a modern, efficient way to view and manage document analysis reports. The new interface features a table-based layout with detailed sidebars, inspired by modern data management applications.

## Interface Overview

The reports interface consists of three main components:

1. **Main Reports View**: Table or card view of all reports
2. **Reports Overview Sidebar**: System statistics and filtering options
3. **Individual Report Sidebar**: Detailed information for selected reports

## Main Reports View

### Table View

The default table view provides a comprehensive overview of all reports:

#### Columns

- **Name**: Document filename with file icon
- **Status**: Current processing status with color-coded badges
- **Risk Level**: Risk assessment with appropriate color coding
- **File Type**: Document format (PDF, DOCX, etc.)
- **File Size**: Human-readable file size
- **Entities**: Number of detected sensitive entities
- **Created**: Creation date and time
- **Updated**: Last modification date and time
- **Actions**: Quick action menu

#### Features

- **Sortable Columns**: Click any column header to sort ascending/descending
- **Row Selection**: Click any row to view details in the sidebar
- **Hover Effects**: Visual feedback when hovering over rows
- **Selected Row Highlighting**: Currently selected row is highlighted
- **Responsive Design**: Adapts to different screen sizes

### Card View

An alternative view that displays reports as individual cards:

- **Visual Layout**: Each report displayed as a card with key information
- **Status Badges**: Prominent display of status and risk level
- **Statistics Table**: Mini table within each card showing key metrics
- **Action Menu**: Dropdown menu for report actions

### Pagination

- **Page Size**: 20 reports per page (configurable)
- **Navigation Controls**: Previous/Next buttons with page information
- **Total Count**: Display of total reports and current page
- **Disabled States**: Appropriate button states when at first/last page

### Header Actions

The header contains several action buttons:

- **View Mode Toggle**: Switch between table and card views
- **Add Report**: Create new document analysis reports
- **Refresh**: Reload the reports list from the server
- **Statistics**: Toggle the reports overview sidebar

## Reports Overview Sidebar

The overview sidebar provides system-wide statistics and filtering capabilities:

### Filter Section

- **Status Filter**: Filter by processing status
  - All statuses
  - Completed
  - Processing
  - Failed
  - Pending

- **Risk Level Filter**: Filter by risk assessment
  - All risk levels
  - High risk
  - Medium risk
  - Low risk

### Statistics Section

Displays comprehensive system statistics:

- **Total Reports**: Count of all reports in the system
- **Total File Size**: Combined size of all analyzed files
- **Risk Distribution**: Breakdown by risk level
- **Status Distribution**: Breakdown by processing status

### Recent Activity Section

Shows the 10 most recently created or updated reports:

- **Report Name**: Filename with file icon
- **Status Badge**: Current processing status
- **Creation Date**: When the report was created

## Individual Report Sidebar

When a report is selected, the detail sidebar opens with tabbed information:

### Overview Tab

- **Status & Risk Section**:
  - Status badge with current processing state
  - Risk level badge with color coding
  - Risk score visualization (circular progress indicator)
  - Risk explanation text

- **File Information Section**:
  - File path, type, extension
  - File size and hash
  - Node ID for tracking

- **Error Information** (if applicable):
  - Error messages for failed processing

### Entities Tab

- **Entity Summary**: Count of entities and types detected
- **Entity List**: Detailed list of all detected entities
  - Entity type with formatted names
  - Confidence score as percentage
  - Actual text that was detected
  - Monospace formatting for easy reading

### Compliance Tab

- **WCAG Compliance**: Results from accessibility analysis
- **Language Level**: Readability and language complexity analysis
- **Formatted Results**: Key-value pairs with human-readable labels

### Retention Tab

- **Retention Policy**: Data retention settings
  - Retention period in days
  - Expiry date
  - Legal basis for processing
  - Purpose of data processing

### Sidebar Actions

Secondary action buttons in the sidebar header:

- **Edit Report**: Open the report editing modal
- **Download Report**: Download the analysis results
- **Delete Report**: Remove the report (with confirmation)

## Technical Implementation

### Vue Components

The interface is built using several Vue.js components:

- **ReportsIndex.vue**: Main reports view with table/card modes and integrated sidebars (`src/views/reports/ReportsIndex.vue`)
- **ReportsSideBar.vue**: Overview sidebar with statistics (`src/sidebars/reports/ReportsSideBar.vue`)
- **ReportSideBar.vue**: Individual report detail sidebar (`src/sidebars/reports/ReportSideBar.vue`)

### File Structure

```
src/
├── views/
│   ├── reports/
│   │   └── ReportsIndex.vue          # Main reports interface
│   └── Views.vue                     # Main view router
├── sidebars/
│   ├── reports/
│   │   ├── ReportsSideBar.vue        # Overview sidebar
│   │   └── ReportSideBar.vue         # Detail sidebar
│   └── SideBars.vue                  # Sidebar manager
└── navigation/
    └── MainMenu.vue                  # Updated navigation menu
```

### State Management

- **Navigation Store**: Manages sidebar visibility states
- **Object Store**: Handles report data and active selections
- **Reactive Updates**: Real-time updates when data changes

### Styling

- **Nextcloud Design System**: Uses official Nextcloud Vue components
- **CSS Variables**: Consistent theming with Nextcloud colors
- **Responsive Design**: Mobile-friendly layouts
- **Accessibility**: Proper ARIA labels and keyboard navigation

### Performance Features

- **Pagination**: Efficient handling of large datasets
- **Lazy Loading**: Components load only when needed
- **Optimized Rendering**: Virtual scrolling for large lists
- **Caching**: Intelligent caching of report data

## User Experience Improvements

### Before the Refactor

- Simple list view with limited information
- Separate detail page requiring navigation
- No filtering or sorting capabilities
- Limited overview of system status

### After the Refactor

- Rich table view with sortable columns
- Sidebar-based details without page navigation
- Advanced filtering and statistics
- Comprehensive system overview
- Improved visual hierarchy and information density

## Accessibility Features

- **Keyboard Navigation**: Full keyboard support for all interactions
- **Screen Reader Support**: Proper ARIA labels and descriptions
- **High Contrast**: Support for high contrast themes
- **Focus Management**: Logical tab order and focus indicators
- **Semantic HTML**: Proper use of table elements and headings

## Browser Compatibility

The interface is compatible with:

- **Modern Browsers**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- **Mobile Browsers**: iOS Safari, Chrome Mobile, Firefox Mobile
- **Accessibility Tools**: NVDA, JAWS, VoiceOver support

## Future Enhancements

Planned improvements for future versions:

- **Advanced Search**: Full-text search across report content
- **Bulk Actions**: Select multiple reports for batch operations
- **Export Options**: Export filtered results to CSV/Excel
- **Custom Columns**: User-configurable table columns
- **Saved Filters**: Save and reuse common filter combinations 