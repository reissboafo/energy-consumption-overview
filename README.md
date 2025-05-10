# Energy Consumption Dashboard

A web-based dashboard for visualizing and analyzing energy consumption data across different locations. Built with PHP, MySQL, Chart.js, and Leaflet.js.

## Features

- 📊 Interactive data visualization with Chart.js
- 🗺️ Geographic data visualization using Leaflet.js
- 📈 Advanced statistical analysis and correlation matrix
- 📁 CSV data import functionality
- 📱 Responsive design
- 🔍 Detailed data analysis per location
- 🎯 Low tariff percentage tracking
- 📍 Location-based consumption patterns

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- MAMP or similar local development environment

## Installation

1. Clone the repository to your web server directory:
```bash
git clone https://github.com/yourusername/energy-dashboard.git
```

2. Import the database structure:
```bash
mysql -u your_username -p < database.sql
```

3. Configure your database connection:
   - Copy `config.example.php` to `config.php`
   - Update the database credentials in `config.php`

4. Ensure the `uploads` and `cache` directories are writable:
```bash
chmod 777 uploads cache
```

5. Access the dashboard through your web browser.

## Usage

1. Upload CSV files containing energy consumption data
2. View interactive charts and maps
3. Analyze consumption patterns
4. Export and share insights

## Data Format

The dashboard expects CSV files with the following columns:
- `zipcode_from`: Starting zipcode of the area
- `zipcode_to`: Ending zipcode of the area
- `city`: City name
- `annual_consume`: Annual energy consumption in kWh
- `annual_consume_lowtarif_perc`: Percentage of consumption at low tariff

Additional columns will be automatically detected and included in the analysis.

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## Security

- Input validation and sanitization implemented
- CSRF protection included
- SQL injection prevention measures
- XSS protection
- Secure file upload handling

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Acknowledgments

- OpenStreetMap for map data
- Chart.js for visualization
- Leaflet.js for mapping functionality
- Bootstrap for UI components
