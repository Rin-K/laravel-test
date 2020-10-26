# laravel-test
?
# Running a command

「
 php artisan import:json source_address_file_name import_data.json
」

These commands will load source-address file from storage path

# Defining Source address 
Source address file must be defined before doing import and should follow the outlined example:

json
{
    "model": "App\\Address_map",
    "data_map": {
        "city": "臺北市",
        "data": [
            {
                "zip": "100",
                "filename": "100",
                "area": "中正區"
            }
        ]
    }
}
