CREATE DATABASE IF NOT EXISTS clearance_uat
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE DATABASE IF NOT EXISTS clearance_prod
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'clearance_uat_user'@'%' IDENTIFIED BY 'uat_password';
CREATE USER IF NOT EXISTS 'clearance_prod_user'@'%' IDENTIFIED BY 'prod_password';

GRANT ALL PRIVILEGES ON clearance_uat.* TO 'clearance_uat_user'@'%';
GRANT ALL PRIVILEGES ON clearance_prod.* TO 'clearance_prod_user'@'%';

FLUSH PRIVILEGES;
