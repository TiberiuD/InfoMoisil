#include "Config.h"
#include <iostream>
#include <fstream>
#include <vector>

bool file_exists(const std::string& filename) {
	std::ifstream ifile(filename.c_str());
	return (bool)ifile;
}

bool readConfigFile(config &configInfo) {
	YAML::Node config;
	struct config tempConfig;

	try {
		// Check whether the configuration file exists or not
		if (file_exists("config.yaml"))
			config = YAML::LoadFile("config.yaml");
		else {
			std::cout << "ERROR: Could not open the configuration file." << std::endl;
			return false;
		}
		
		// Read the database configuration
		if (config["database"]) {
			if (config["database"]["hostname"]) {
				tempConfig.db_hostname = config["database"]["hostname"].as<std::string>();
			}
			else {
				std::cout << "ERROR: Database hostname is not present in the configuration file." << std::endl;
				return false;
			}

			if (config["database"]["port"] && !config["database"]["port"].IsNull()) {
				tempConfig.db_port = config["database"]["port"].as<std::string>();
			}
			else {
				tempConfig.db_port = "3306";
				std::cout << "WARNING: Database port isn't specified. Using default." << std::endl;
			}

			if (config["database"]["username"]) {
				tempConfig.db_username = config["database"]["username"].as<std::string>();
			}
			else {
				std::cout << "ERROR: Database username is not present in the configuration file." << std::endl;
				return false;
			}

			if (config["database"]["password"]) {
				tempConfig.db_password = config["database"]["password"].as<std::string>();
			}
			else {
				std::cout << "ERROR: Database password is not present in the configuration file." << std::endl;
				return false;
			}

			if (config["database"]["database"]) {
				tempConfig.db_database = config["database"]["database"].as<std::string>();
			}
			else {
				std::cout << "ERROR: Database name is not present in the configuration file." << std::endl;
				return false;
			}
		}
		else {
			std::cout << "ERROR: Database information is not present in the configuration file." << std::endl;
			return false;
		}

		// To be read from the program
		tempConfig.database_refresh_time = 10000;
		tempConfig.queue_max_size = 10;
	}
	catch (YAML::Exception &e) {
		std::cout << "ERROR: Unexpected YAML error while reading the configuration file: " << e.msg << std::endl;
		return false;
	}

	configInfo = tempConfig;
	return true;
}