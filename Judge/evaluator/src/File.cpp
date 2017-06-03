#include "File.h"
#include <sstream>
#include <fstream>

File::File(sql::Connection *DBCon, int fileID) {
	this->fileID = fileID;

	sql::Statement *stmt = NULL;
	sql::ResultSet *res = NULL;

	try {
		std::stringstream query;

		query << "SELECT `filename`, `binary`, `hash` FROM `files` WHERE `id` = "
			<< this->fileID;

		stmt = DBCon->createStatement();
		res = stmt->executeQuery(query.str());
		while (res->next()) {
			this->fileName = res->getString("filename");
			this->binaryData = res->getBlob("binary");
			this->hash = res->getString("hash");
		}
	}
	catch (sql::SQLException &e) {
		std::cout << "# ERR: SQLException in " << __FILE__;
		std::cout << "(" << __FUNCTION__ << ") on line " << __LINE__ << std::endl;
		std::cout << "# ERR: " << e.what();
		std::cout << " (MySQL error code: " << e.getErrorCode();
		std::cout << ", SQLState: " << e.getSQLState() << " )" << std::endl;
	}
}

void File::writeFile(boost::filesystem::path targetDirectory) {
	if (!boost::filesystem::exists(targetDirectory))
		throw "Specified directory doesn't exist!";
	
	boost::filesystem::path filePath = targetDirectory / this->fileName;
	
	std::ofstream fout(filePath.generic_string(), std::ofstream::binary);
	if (!fout)
		std::cout << "Could not open file " + targetDirectory.generic_string() + this->fileName + " for writing!";


	char buff[1048];
	std::string s;

	while (!this->binaryData->eof())
	{
		this->binaryData->read(buff, sizeof(buff));
		s.append(buff, this->binaryData->gcount());
	}

	fout << s;
	fout.close();
}

void File::setFileName(std::string fileName) {
	this->fileName = fileName;
}

std::string File::getFileName() {
	return this->fileName;
}

File::~File() {
	delete this->binaryData;
}
