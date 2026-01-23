# NIDA Parse

A Laravel package that attempts to reverse engineer how NIDA (National Identification Authority) numbers are generated and extract basic information from National Identification Numbers (NIN) without using the official NIDA API.

This is a fork of the Python implementation: [reverse_nida](https://github.com/Henryle-hd/reverse_nida)

## ⚠️ Disclaimer

This project is for educational and research purposes only. It attempts to understand the structure and patterns in NIDA numbers through reverse engineering. The accuracy of extracted information is not guaranteed, and this should not be used for official verification purposes.

## 🚀 Installation

### Requirements

- PHP >= 8.2
- Composer
- Laravel 11.x or 12.x

### Install via Composer

```bash
composer require ngarak-dev/nida-parse
```

### Publish Configuration and Assets

Publish the configuration file:

```bash
php artisan vendor:publish --tag=nida-parse-config
```

Publish the CSV location files to storage:

```bash
php artisan vendor:publish --tag=nida-parse-csv
```

Or publish both at once:

```bash
php artisan vendor:publish --provider="NidaParse\NidaParseServiceProvider"
```

## 📖 Usage

### Using the Helper Function

```php
// The helper function is autoloaded globally
// Analyze a NIDA number (supports both dashed and compact formats)
$ninDashed = "19990504-35710-00001-28";
$ninCompact = "19990504357100000128";

// Get basic information
$info = get_basic_info($ninDashed, null, false);
print_r($info);
// Output: [
//   'BIRTHDATE' => '1999-05-04',
//   'GENDER' => 'FEMALE',
//   'REGIONCODE' => '35',
//   'REGION' => 'MOROGORO',
//   'DISTRICT' => 'MOROGORO URBAN',
//   'WARDCODE' => '35710',
//   'WARD' => 'Mazimbu',
//   'STREET' => '',
//   'PLACES' => '',
// ]

// Get full information with debug
$info = get_basic_info("19990504-35710-00001-28", null, true);
```

### Using the Service Classes Directly

```php
use NidaParse\Services\NidaService;
use NidaParse\Services\LocationLookup;
use NidaParse\Services\NidaParser;

// Parse NIN
$parsed = NidaParser::parse("19990504-35710-00001-28");

// Lookup location
$lookup = new LocationLookup();
$location = $lookup->getAdministrativeHierarchy("35710");

// Get complete info using dependency injection
$service = app(NidaService::class);
$info = $service->getBasicInfo("19990504-35710-00001-28");
```

### Using Dependency Injection

```php
use NidaParse\Services\NidaService;

class YourController extends Controller
{
    public function __construct(
        protected NidaService $nidaService
    ) {}

    public function parseNin(string $nin)
    {
        $info = $this->nidaService->getBasicInfo($nin);
        return response()->json($info);
    }
}
```

## 🔧 Configuration

After publishing the configuration file, you can customize the package behavior in `config/nida-parse.php`:

```php
return [
    'csv_directory' => storage_path('app/location_files_code'),
    'combined_csv_path' => null, // Set to path of combined CSV if available
];
```

### How Location Lookup Works

The package uses a smart lookup strategy:

1. **Combined File (Preferred)**: If `tanzania_locations_combined.csv` exists in `storage/app/` (after publishing), it will be used first for faster lookups.
2. **Individual Files (Fallback)**: If the combined file is not found or doesn't contain the ward code, the package falls back to individual region CSV files (11.csv, 21.csv, etc.).

The combined file is automatically published when you run:
```bash
php artisan vendor:publish --tag=nida-parse-csv
```

You can also override the CSV directory or combined file path when instantiating `LocationLookup`:

```php
// Custom CSV directory
$lookup = new LocationLookup('/custom/path/to/csv/files');

// Custom combined file path
$lookup = new LocationLookup(null, '/path/to/combined.csv');
```

## 📝 NIN Format

The package supports two NIN formats:

1. **Dashed format**: `YYYYMMDD-LLLLL-SSSSS-XX`
   - Example: `19990504-35710-00001-28`

2. **Compact format**: `YYYYMMDDWWWWWSSSSSXX`
   - Example: `19990504357100000128`

Where:

- `YYYYMMDD`: Birth date (Year, Month, Day)
- `LLLLL`: Ward code (5 digits)
- `SSSSS`: Sequence number (5 digits)
- `XX`: Unknown/check digits (2 digits, first digit indicates gender)

## 📁 Package Structure

```
nida-parse/
├── src/
│   ├── Services/
│   │   ├── LocationLookup.php
│   │   ├── NidaParser.php
│   │   └── NidaService.php
│   ├── Helpers/
│   │   └── nida_helper.php
│   └── NidaParseServiceProvider.php
├── config/
│   └── nida-parse.php
├── resources/
│   ├── location_files_code/
│   │   ├── 11.csv
│   │   ├── 21.csv
│   │   └── ... (other region CSV files)
│   └── tanzania_locations_combined.csv
└── composer.json
```

## 🧪 Testing

```bash
composer test
```

## 📄 License

MIT

## 🙏 Credits

- Original Python implementation by [Henrylee](https://github.com/Henryle-hd/reverse-nida)
- Laravel package conversion by Ngara Wambura

## 🔗 Links

- [Original Python Package](https://github.com/Henryle-hd/reverse_nida)
- [PyPI Package](https://pypi.org/project/r-nida/)
