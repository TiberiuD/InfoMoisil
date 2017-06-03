#ifndef JOB_H_INCLUDED
#define JOB_H_INCLUDED

#include "mysql_connection.h"

#include <cppconn/driver.h>
#include <cppconn/exception.h>
#include <cppconn/resultset.h>
#include <cppconn/statement.h>
#include <cppconn/prepared_statement.h>

#include <boost/filesystem.hpp>

#include "File.h"
#include "User.h"

#include <queue>

std::queue<int> getJobs(sql::Connection *DBCon, int maxJobs);

struct JobTest {
	int testID;
	File *input, *output;
	int points;
};

struct ProblemInfo {
	int problemID;
	std::string name;
	
	bool isFileInput;
	int maxTime;
	int maxMemory;

	int points;
};

class Job {
private:
	int jobID;
	User *user;
	int problemID;
	int score;
	File *source;
	ProblemInfo problemInfo;

	std::string language;
	boost::filesystem::path jobPath;

	std::string compilerOutput;

	ProblemInfo getProblemInfo(sql::Connection *DBCon);
	std::queue<JobTest> getJobTests(sql::Connection *DBCon);

public:
	Job(sql::Connection *DBCon, int jobID);
	void compileSource();
	void runTests(sql::Connection *DBCon);
	void updateJobTest(sql::Connection *DBCon, int TestID, bool status);
	void updateDatabase(sql::Connection *DBCon);
	~Job();
};

#endif

