### Overview

A custom module called eden will be created in the modules/custom directory.

This custom module will have the following features:

1. An incident management function that allows users to create, update, and delete incidents.

2. A victim management function that allows users to create, update, and delete victims.

3. A lexicon of human rights violations that allows users to create, update, and delete human rights violations. 

### Relationships of entities

1. The victim entity is used to store the victims. This entity must act as a standard entity. 

2. An incident has many victims. For every victim, a human rights violation must be selected. human rights violation can be multiple.

3. A human rights violation entity is used to store the human rights violations. This entity must act as a standard entity.

4. A sector entity is used to store the sectors. This entity must act as a standard entity.

5. A victim can be associated with Sector. Sector association can be multiple.

6. A Perpetrator entity is used to store the perpetrators. This entity must act as a standard entity. 

7. A perpetrator can be associated with the incident. An incident can have multiple perpetrators.

8. A Town-Province-Region entity is used to store the towns, provinces, and regions. This entity must act as a standard entity.

9. A Town-Province-Region entity can be associated with the incident. 

10. A Town-Province-Region entity can be associated with the victim. 

### Common fields
- uuid
- created
- changed
- uid
- status
- langcode
- revision_id
- revision_created
- revision_user
- revision_log
- revision_translation_affected
- moderation_state

### Entity types

1. Incident

2. Victim

3. Human rights violation

4. Sector

### Entity fields

1. Incident
    - Case Number - pattern: EDN-YYYY-MM-<serial number>. Serial number is auto-generated but sequential.
    - Involving Children (boolean, default to false)
    - Mining-related (boolean, default to false)
    - Agrarian-related (boolean, default to false)
    - Demolition-related (boolean, default to false)
    - Title
    - Account of Incident
        - Victims (count of victims)
        - Families (count of families)
        - Perpetrators (count of perpetrators)
    - Date of incident (restrict to date only YYYY-MM-DD)
    - Unspecified Date (boolean, default to false)
    - Incident is continuing (boolean, default to false)
    - Location
    - Filing Date (restrict to date only YYYY-MM-DD, default to today's date)

2. Victim
    - Victim Type (options: Individual, Family, Community, Group, Organization)
    - Group Name (if Victim Type is not Individual, optional)
    - First Name (if Victim Type is Individual, required)
    - Last Name (if Victim Type is Individual, required)
    - Middle Name (if Victim Type is Individual, optional)
    - Occupation (if Victim Type is Individual, optional)
    - Sector (must use the sector entity, optional if Victim Type is Individual)
    - Birthdate (restrict to date only YYYY-MM-DD, optional only if Victim Type is Individual) or Age (optional only if Victim Type is Individual)  
    - Gender (options: Male, Female, Other, optional only if Victim Type is Individual)
    - Civil Status (options: Single, Married, Divorced, Widowed, optional only if Victim Type is Individual)
    - Ethnicity (or Ethnic Group)
    - No of Children (optional, if Victim Type is Individual)
    - No. of Children Below 18 y.o. (optional, if Victim Type is Individual)
    - Residence (Location)
    - Affiliation (Organization, optional if Victim Type is Individual)
        - Organization Name
        - Position
        - Other Affiliation
    - Remarks

3. Human rights violation
    - Violation
    - Description   
    - Category

4. Incident victim
    - Incident
    - Victim
    - Details of Detention
        - Date of Detention (restrict to date only YYYY-MM-DD)
        - Place of Arrest
        - Place of Detention
        - Charges
        - Already Released (boolean, default to false)
        - Remarks on Release

5. Incident Victim Violation
    - Incident
    - Victim
    - Violation 

6. Sector
    - Name
    - Sector code
    - Description
    - the list of values must be taken from the migrate_hrmon_sector table. One time migration must be done to import the values.

7. Perpetrator
    - Group (options: AFP, AFP-ARMY, AFP-NAVY, AFP-AIRFORCE, PNP, CAFGU, LGU, BPSO, CVO, CAA, NGU, PRIVATE, PARAMILITARY OTHER)
    - UNIT
    - Brief Info 
    - CO
    - Location
    - Remarks

8. Town-Province-Region Entity
    - Town
    - Province
    - Region    

9. Incident Case Update
    - create a content type called Case Update
    - Incident
    - Document Attachment (can be multiple)
        - File Upload
        - Document Description
        - File date (restrict to date only YYYY-MM-DD)

### Content Management
1. All content must have an add, edit, and delete form.
2. All content must have a view page that displays the content.
3. All content must have a listing page that displays a list of all content.
4. Interrelationships must be displayed on the view page.
5. Interrelationships must be pre-populated on the add form.

### Installation and Uninstallation
1. all schema changes must be in the eden.install file.
2. all database creation objects must be included in the install() function.
3. all database deletion objects must be included in the uninstall() function. Skip objects that are not found in the database. Handle errors gracefully.

### Dev environment
1. ddev is used to manage the dev environment.
2. commands must use ddev. 

<!-- MxhTghdUqm -->

<!-- Creating entity type definitions
Adding entity fields
Setting up entity relationships
Creating forms
Setting up views and routes -->

<!-- Creating the relationship tables and fields
Creating the list builders
Creating the forms
Setting up the views -->

<!-- Setting up the views
Implementing access control
Adding custom validation and form alterations if needed -->
