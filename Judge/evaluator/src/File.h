#ifndef FILE_H_INCLUDED
#define FILE_H_INCLUDED

#include <boost/filesystem.hpp>

#include "mysql_connection.h"

#include <cppconn/driver.h>
#include <cppconn/exception.h>
#include <cppconn/resultset.h>
#include <cppconn/statement.h>

class File {
private:
	int fileID;
	std::string fileName;
	std::istream *binaryData;
	std::string hash;
	// (sthsth) dateModified;

public:
	File(sql::Connection *DBCon, int fileID);
	void writeFile(boost::filesystem::path targetDirectory);
	void setFileName(std::string fileName);
	std::string getFileName();
	~File();
};

#endif

