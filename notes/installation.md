# Peanut site installation steps.

1. Create the peanut application database using the supplied SQL scripts. See 'Database Configuration' section below.
2. Install Concrete CMS.
2. In URLs and Redirection, Check "Pretty URLs" and make sure that a correct .htaccess file is created.
2. Choose a Concrete CMS theme. If you are not using the Peanut Theme, add the view model loading code snippet to the
bottom of the footer (usually footer_bottom.php). See [theme-notes.md](theme-notes.md)
2. Unzip tq-peanut.zip to the document root folder, to create the tq-peanut sources directory.
3. Update the configuration files in tq-peanut\config directory as needed. Typically the files that are different in 
production environments are:
   - settings.ini
   - database.ini
   - classes.ini
4. Unzip tq_peanut_package.zip to the Concrete CMS packages directory.
5. Unzip the theme_peanut.zip to the Concrete CMS packages directory.
6. Install the Tq_Peanut package.
7. Install the theme package.
8. Activate the theme.

## Database Configuration

The peanut database may be separate from the Concrete CMS database. This is convenient in certain cases. To enable use 
of one database for both Peanut and Concrete CMS tables, add the following to `tq-peanut/config/classes.ini`

```ini
[tops.connections]
type='Tops\concrete5\Concrete5ConnectionManager'
```

To enable a second database for Peanut tables. Update the `tq-peanut/config/database.ini` file and 
remove or comment out the `[tops.connections]` section in classes.ini.
