# Social Platform - PHP Backend

A complete social platform with real-time chat, direct messaging, and calendar functionality built with PHP, MySQL, and Server-Sent Events.

## Features

- **User Authentication**: JWT-based login/registration system
- **Real-time Chat Room**: Public messaging visible to all users
- **Direct Messages**: Private messaging between users
- **Calendar System**: Personal calendar with event management
- **User Status**: Online/offline status tracking
- **Real-time Updates**: Server-Sent Events for instant message delivery
- **Responsive Design**: Works on desktop and mobile devices

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server (or Nginx with PHP-FPM)
- PDO MySQL extension
- OpenSSL extension (for JWT)

## Installation

### 1. Download Files

Create your project directory and add all the files:

```
your-project/
├── config.php          # Database configuration
├── auth.php            # Authentication API
├── messages.php        # Chat and DM API
├── users.php           # Users API
├── events.php          # Calendar events API
├── realtime.php        # Server-Sent Events
├── index.html          # Frontend application
├── .htaccess           # Apache configuration
├── setup.sql           # Database structure
└── README.md           # This file
```

### 2. Database Setup

1. Create a MySQL database:
```sql
CREATE DATABASE social_platform;
```

2. Import the database structure:
```bash
mysql -u your_username -p social_platform < setup.sql
```

3. Or run the SQL commands from `setup.sql` in your MySQL client.

### 3. Configure Database Connection

Edit `config.php` and update the database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_mysql_username');
define('DB_PASS', 'your_mysql_password');
define('DB_NAME', 'social_platform');
```

### 4. Web Server Setup

#### Apache
1. Ensure `mod_rewrite` is enabled
2. Place files in your web root directory (e.g., `/var/www/html/` or `htdocs/`)
3. Make sure `.htaccess` files are allowed

#### Nginx
Add this to your Nginx configuration:
```nginx
location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}

location /api/ {
    try_files $uri $uri/ @php;
}

location @php {
    rewrite ^/api/(.+)$ /$1.php last;
}
```

### 5. File Permissions

Make sure PHP can read all files:
```bash
chmod 644 *.php *.html
chmod 755 .
```

## Usage

### 1. Access the Application

Open your web browser and navigate to your server:
- Local development: `http://localhost/your-project/`
- Production: `https://yourdomain.com/`

### 2. Create Account

1. Click "Don't have an account? Sign Up"
2. Enter username, password, and optionally email
3. Click "Sign Up"

### 3. Features

- **Chat Room**: Send messages visible to all users
- **Direct Messages**: Click on users in the sidebar to start private conversations
- **Calendar**: Click on calendar days to add events
- **Real-time**: Messages appear instantly without refreshing

## API Endpoints

### Authentication
- `POST /auth.php?action=register` - Register new user
- `POST /auth.php?action=login` - Login user

### Messages
- `GET /messages.php?type=chat` - Get chat room messages
- `POST /messages.php?type=chat` - Send chat message
- `GET /messages.php?type=direct&user_id=ID` - Get direct messages
- `POST /messages.php?type=direct` - Send direct message

### Users
- `GET /users.php` - Get list of users

### Events
- `GET /events.php?month=YYYY-MM` - Get calendar events
- `POST /events.php` - Create new event
- `DELETE /events.php?id=ID` - Delete event

### Real-time
- `GET /realtime.php` - Server-Sent Events stream

## Security Features

- JWT-based authentication
- Password hashing with PHP's `password_hash()`
- SQL injection prevention with prepared statements
- CORS headers configuration
- Input validation and sanitization

## Development

### Testing Multiple Users

1. Open multiple browser windows/tabs
2. Register different users in each
3. Test real-time messaging between them

### Database Schema

- **users**: User accounts and status
- **chat_messages**: Public chat room messages
- **direct_messages**: Private messages between users
- **calendar_events**: Personal calendar events
- **active_sessions**: Real-time session tracking

### Customization

- Modify `config.php` for database and JWT settings
- Update styles in `index.html` for different appearance
- Extend API endpoints for additional features

## Production Deployment

### Security Checklist

1. **Change JWT Secret**: Update `JWT_SECRET` in `config.php`
2. **Use HTTPS**: Enable SSL/TLS for all connections
3. **Database Security**: Use strong MySQL passwords and restricted user accounts
4. **File Permissions**: Ensure proper file permissions (644 for files, 755 for directories)
5. **Hide Config**: Block direct access to `config.php` via web server configuration
6. **Error Logging**: Configure PHP error logging, disable display_errors in production

### Performance Optimization

1. **Database Indexes**: The setup includes indexes for better performance
2. **Connection Pooling**: Configure MySQL connection pooling if using high traffic
3. **Caching**: Implement Redis or Memcached for session storage
4. **CDN**: Use a CDN for static assets

### Scaling Considerations

- For high traffic, consider using WebSocket servers like Socket.io with Node.js
- Implement message queuing systems for reliable message delivery
- Use database replication for read/write splitting
- Consider horizontal scaling with load balancers

## Troubleshooting

### Common Issues

1. **Database Connection Errors**
   - Check database credentials in `config.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **Real-time Not Working**
   - Check if Server-Sent Events are supported by your browser
   - Verify PHP error logs for issues in `realtime.php`
   - Ensure proper CORS headers

3. **Authentication Issues**
   - Check JWT secret configuration
   - Verify token storage in browser localStorage
   - Check PHP session configuration

4. **Messages Not Sending**
   - Check browser console for JavaScript errors
   - Verify API endpoints are accessible
   - Check database permissions

### Debug Mode

Add this to `config.php` for development debugging:
```php
// Add at the top after <?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

**Remember to remove debug settings in production!**

## License

This project is open source and available under the MIT License.