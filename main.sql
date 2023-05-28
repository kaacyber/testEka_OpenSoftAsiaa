# 1 Task

# table: salary
# | id: integer | employee_id: integer | date: datetime | value: integer |
#
# TASK 1: select second biggest salary by `value` of employee with ID = 5 for 2021 year (you have to write native SQL query for MySQL 5.7)

SELECT value
FROM salary
WHERE employee_id = 5 AND YEAR(date) = 2021
ORDER BY value DESC
LIMIT 1, 1;

# 2 Task

# table: warehouse 

# id: integer
# latitude: double
# longitude: double
# title: string

# TASK 2: Please select the nearest warehouse from the center of Denpasar city in the 10km radius (you have to write native SQL query for MySQL 5.7)

SELECT id, latitude, longitude, title,
    (6371 * ACOS(COS(RADIANS(-8.6705)) * COS(RADIANS(latitude)) * COS(RADIANS(longitude) - RADIANS(115.2126)) + SIN(RADIANS(-8.6705)) * SIN(RADIANS(latitude))))
    AS distance
FROM tb_warehouse
HAVING distance <= 10
ORDER BY distance ASC
LIMIT 1;
