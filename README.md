# CherryTree-WebEditor

A clean, web-based editor for notes exported from <a href="https://github.com/giuspen/cherrytree">CherryTree</a> to a set of HTML files.

## Features

- 📝 **Split-pane editor** - Edit HTML on the left, see live preview on the right
- 🔍 **Search functionality** - Quickly find notes by title or filename
- ➕ **Create new notes** - Add new entries with proper formatting (sorting not implemented)
- 💾 **Auto-save** - Save changes with a single click
- 🗑️ **Delete notes** - Remove unwanted entries
- 🎨 **Clean, focused design** - Distraction-free editing environment

## Installation

### 1. Upload Files

Upload these two files to your notes directory:

```
/var/www/domain.tld/notes/nowak.php
/var/www/domain.tld/notes/kowalski.htm
```

### 2. Set Permissions

Make sure the PHP script can read and write files:

```bash
cd /var/www/domain.tld/notes/
chown www-data:www-data kowalski.htm nowak.php
chmod www-data:www-data .  # Ensure directory is owned by the PHP user
```
Default file permissions should mean these are writable.

### 3. Verify PHP Configuration

The API requires PHP to be installed and configured on your server. It should work with most standard PHP installations (PHP 7.0+).

### 4. Test the Installation

Visit: `https://domain.tld/notes/kowalski.htm`

You should see the editor interface with your existing notes listed in the sidebar.

## Usage

### Editing Notes

1. **Select a note** from the sidebar
2. **Edit the HTML** in the left pane
3. **Preview changes** in the right pane
4. **Click "Save"** to write changes to disk

### Creating New Notes

1. Click **"+ New Note"** button
2. Enter a **title** (this appears in the page title and H1)
3. Enter a **filename** (e.g., `Topic_Notes--Category--Topic_123`)
   - The `.html` extension is added automatically
   - Follow your existing naming convention
4. Click **"Create"**

### Searching Notes

Use the search box to filter notes by title or filename. The list updates in real-time.

### Deleting Notes

1. Open the note you want to delete
2. Click the **"Delete"** button in the header
3. Confirm the deletion

## File Structure

The editor works with the CherryTree export structure as-of several years ago when I last used it. Your results may vary:

```html
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Your Note Title</title>
  <meta name="generator" content="CherryTree">
  <link rel="stylesheet" href="res/styles4.css" type="text/css" />
</head>
<body>
<div class='page'>
  <h1 class='title'>Your Note Title</h1>
  <br/>
  <p>Your content here...</p>
</div>
</body>
</html>
```

## API Endpoints

The `nowak.php` file provides these endpoints:

- **GET** `?action=list` - List all notes
- **GET** `?action=read&file=filename.html` - Read a specific note
- **POST** `?action=save` - Save changes to a note
- **POST** `?action=create` - Create a new note
- **POST** `?action=delete` - Delete a note

## Security Notes

### Current Setup (Rushed)
- No authentication required
- Security through obscurity (unpublished URL)
	- Give the editor and API files random names when you install it
	- Update the name of the file in nowak.php
- Suitable for private/internal use

### Recommended for Production

If you want to add authentication, you can:

1. **Add HTTP Basic Auth** via `.htaccess`:
   ```apache
   <Files "kowalski.htm">
       AuthType Basic
       AuthName "Notes Editor"
       AuthUserFile /path/to/.htpasswd
       Require valid-user
   </Files>
   
   <Files "nowak.php">
       AuthType Basic
       AuthName "Notes Editor"
       AuthUserFile /path/to/.htpasswd
       Require valid-user
   </Files>
   ```

2. **Use IP whitelisting** in `.htaccess`:
   ```apache
   <FilesMatch "(kowalski\.htm|nowak\.php)">
       Order deny,allow
       Deny from all
       Allow from YOUR.IP.ADDRESS
   </FilesMatch>
   ```

3. **Add session-based authentication** in the PHP code

## Troubleshooting

### "Failed to load notes"
- Check that `nowak.php` is accessible
- Verify file permissions (PHP needs read access)
- Check PHP error logs

### "Failed to save note"
- Verify directory is writable by PHP
- Check file permissions
- Ensure enough disk space

### Notes not appearing
- Verify files end with `.html`
- Check that files match the expected structure
- Look at browser console for JavaScript errors

### Preview not showing correctly
- Ensure CSS file exists at `res/styles4.css`
- Check that HTML is properly formatted
- Look for unclosed tags

### Other Tips
- Check the browser console for JavaScript errors
- Try a different php version

## Future Enhancements

Possible additions we want (someday):

- ✏️ **Markdown support** - Write in markdown, save as HTML
- 🔐 **Authentication** - User login system
- 📊 **Auto-update index.html** - Rebuild the tree navigation automatically instead of adding new notes to the end
- 🏷️ **Tags/categories** - Organize notes with metadata
- 📤 **Export/backup** - Download notes as archive
- 🔄 **Version history** - Track changes over time
- 📱 **Mobile responsive** - Better mobile editing experience

## License

This editor is vibe-coded for unprofessional use with absolutely no warrantee and published with The Unlicense. 

**Assume it will break ALL YOUR THINGS.**

Fork and modify the code as desired.
