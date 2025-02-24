# Eden Module

## Overview
Eden is a comprehensive Human Rights Documentation System for Drupal 11. It provides a robust platform for documenting, tracking, and managing human rights incidents, victims, perpetrators, and violations.

## Features

### Incident Management
- Create and manage detailed incident records
- Track case numbers and filing dates
- Document locations and dates of incidents
- Record comprehensive accounts of incidents
- Track victim and perpetrator counts

### Victim Documentation
- Maintain detailed victim profiles
- Record personal information and demographics
- Track organizational affiliations
- Document detention details:
  - Date of detention
  - Place of arrest
  - Place of detention
  - Charges
  - Release status and remarks

### Perpetrator Tracking
- Document perpetrator groups and units
- Record commanding officers
- Track locations and jurisdictions
- Maintain group affiliations
- Support for various perpetrator types in Philippine context.

### Location Management
- Hierarchical location tracking
- Record towns, provinces, and regions
- Maintain location relationships

### Violation Documentation
- Categorize human rights violations
- Link violations to incidents and victims
- Track violation patterns and trends

## Installation

1. Download the module to your Drupal installation:
   ```bash
   cd modules/custom
   git clone [repository-url] eden
   ```

2. Enable the module using Drush:
   ```bash
   drush en eden
   ```

3. Clear the cache:
   ```bash
   drush cr
   ```

## Configuration

1. Navigate to `Admin > Structure > Eden Settings` to configure:
   - Incident settings
   - Victim settings
   - Perpetrator settings
   - Violation settings
   - Location settings

2. Set up user permissions at `Admin > People > Permissions`:
   - Administer Eden
   - Access Eden content
   - Create/Edit/Delete:
     - Incidents
     - Victims
     - Perpetrators
     - Violations
     - Locations

## Usage

### Managing Incidents
1. Go to `Admin > Content > Eden > Incidents`
2. Click "Add Incident" to create a new incident
3. Fill in the required information:
   - Case details
   - Date and location
   - Incident account
4. Add victims and perpetrators through the respective tabs

### Recording Victims
1. Navigate to `Admin > Content > Eden > Victims`
2. Click "Add Victim" to create a new victim record
3. Enter victim information:
   - Personal details
   - Organizational affiliations
   - Detention information (if applicable)

### Documenting Perpetrators
1. Access `Admin > Content > Eden > Perpetrators`
2. Click "Add Perpetrator" to create a new perpetrator record
3. Provide perpetrator details:
   - Unit/Group information
   - Commanding officer
   - Location and jurisdiction

## Dependencies
- Drupal 11.x
- Entity API
- Views

## Maintainers / Design Architect / Lead Developer
- Rick Bahague

## Code Generation Assistant
- Cursor

## Concept and Design
- Karapatan

## License
This project is licensed under the GNU General Public License v3.0 (GPL-3.0). This means you are free to:
- Use the software for any purpose
- Change the software to suit your needs
- Share the software with your friends and neighbors
- Share the changes you make

For more information, see the [GNU GPL v3 license](https://www.gnu.org/licenses/gpl-3.0.en.html). 