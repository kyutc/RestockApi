# How to update your remote database?

### Step 1 - Ensure that your remote directory is the same as your local
1. Click on `Tools>Deployment>Options` and make sure "Delete target items when source do not exist" is checked.
2. Right click on the root directory `RestockApi` in your project. At the bottom of the dropdown, mouse over `Deployment` and click on "Upload to cpsc4900.local"
### Step 2 - Use the CLI to set up your database
1. Log into the virtual machine and change directory to `/var/www/api.cpsc4900.local/v1/`. If doing this for the first time, or if you run into any issues in the following steps, log into the `mysql` CLI and enter the commands `DROP DATABASE restock;` and `CREATE DATABASE restock;`, then log out of `mysql`.
2. Enter the command `php bin/doctrine.php orm:schema-tool:create`. The output should say "Database created successfully".
3. You can verify the state of the database by logging into `mysql` CLI, using `SHOW TABLES;` to ensure you have 7 tables, one for each `src/Restock/Entity` class file.

