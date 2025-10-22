-- Script to update rental ID 36 with project and building associations
-- First, check what projects and buildings are available

-- View available projects
SELECT id, user_id,
       (SELECT title FROM user_project_contents WHERE project_id = user_projects.id LIMIT 1) as project_title
FROM user_projects
ORDER BY id DESC
LIMIT 10;

-- View available buildings
SELECT id, name, project_id
FROM buildings
ORDER BY id DESC
LIMIT 10;

-- View current rental data
SELECT id, unit_id, project_id, building_id, tenant_full_name, status
FROM rm_rentals
WHERE id = 36;

-- View the property's associations (if any)
SELECT
    p.id as property_id,
    p.project_id as property_project_id,
    p.building_id as property_building_id,
    pc.title as property_title
FROM user_properties p
LEFT JOIN user_property_contents pc ON p.id = pc.property_id
WHERE p.id = (SELECT unit_id FROM rm_rentals WHERE id = 36);

-- OPTION 1: Update rental to use property's project and building
UPDATE rm_rentals
SET
    project_id = (SELECT project_id FROM user_properties WHERE id = rm_rentals.unit_id),
    building_id = (SELECT building_id FROM user_properties WHERE id = rm_rentals.unit_id)
WHERE id = 36;

-- OPTION 2: Manually set specific project and building IDs
-- UPDATE rm_rentals
-- SET
--     project_id = 1,  -- Replace with actual project ID
--     building_id = 1  -- Replace with actual building ID
-- WHERE id = 36;

-- Verify the update
SELECT id, unit_id, project_id, building_id, tenant_full_name, status
FROM rm_rentals
WHERE id = 36;

