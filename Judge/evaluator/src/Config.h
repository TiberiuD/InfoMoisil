#ifndef CONFIG_H_INCLUDED
#define CONFIG_H_INCLUDED

#include "yaml-cpp/yaml.h"

struct config {
	// Database configuration
	std::string db_hostname;
	std::string db_port;
	std::string db_username;
	std::string db_password;
	std::string db_database;

	// Application settings
	int queue_max_size;
	int database_refresh_time;

	// Compiler settings
	char c[256];
	char cpp[256];
	char ps[256];
};

/**
	Reads the program configuration from config.yaml

	@param configInfo A struct where the configuration info to be stored into
	@returns Whether the configuration has been read or not
	If no error occured, the function returns TRUE. Otherwise, a FALSE value
	is returned.
*/
bool readConfigFile(config &configInfo);


#endif