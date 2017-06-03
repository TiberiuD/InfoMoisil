#include "Job.h"
#include "Utils.h"

#include <sstream>
#include <iostream>

using namespace std;

std::queue<int> getJobs(sql::Connection *DBCon, int maxJobs) {
	std::queue<int> jobs;

	sql::Statement *stmt = NULL;
	sql::ResultSet *res = NULL;

	try {
		std::stringstream query;

		query << "SELECT `id`, `priority` FROM `jobs` WHERE `done` = 0 LIMIT " << maxJobs;

		stmt = DBCon->createStatement();
		res = stmt->executeQuery(query.str());
		
		// Add every job in the queue
		while (res->next())
			jobs.push(res->getInt("id"));
	}
	catch (sql::SQLException &e) {
		cout << "# ERR: SQLException in " << __FILE__;
		cout << "(" << __FUNCTION__ << ") on line " << __LINE__ << endl;
		cout << "# ERR: " << e.what();
		cout << " (MySQL error code: " << e.getErrorCode();
		cout << ", SQLState: " << e.getSQLState() << " )" << endl;
	}
	catch (...) {

	}

	return jobs;
}

Job::Job(sql::Connection *DBCon, int jobID) {
	if (DBCon->isClosed()) {
		throw "Dafuq m9 iz closed rly";
	}

	this->jobID = jobID;

	sql::Statement *stmt = NULL;
	sql::ResultSet *res = NULL;

	try {
		std::stringstream query;

		query << "SELECT `user_id`, `problem_id`, `file_id` FROM `jobs` WHERE `id` = "
			<< this->jobID;

		stmt = DBCon->createStatement();
		res = stmt->executeQuery(query.str());
		while (res->next()) {
			this->user = new User(DBCon, res->getInt("user_id"));
			this->problemID = res->getInt("problem_id");
			this->source = new File(DBCon, res->getInt("file_id"));
		}
	}
	catch (sql::SQLException &e) {
		cout << "# ERR: SQLException in " << __FILE__;
		cout << "(" << __FUNCTION__ << ") on line " << __LINE__ << endl;
		cout << "# ERR: " << e.what();
		cout << " (MySQL error code: " << e.getErrorCode();
		cout << ", SQLState: " << e.getSQLState() << " )" << endl;
	}

	std::stringstream path;
	path << "jobs/" << this->jobID;
	this->jobPath = path.str();

	if (boost::filesystem::exists(this->jobPath)) {
		if (boost::filesystem::remove_all(this->jobPath)) {
			cout << "Successfully deleted job folder." << endl;
		}
		else {
			cout << "Could not delete job folder." << endl;
		}
	}

	if (boost::filesystem::create_directories(this->jobPath)) {
		std::cout << "Created job folder." << endl;
		this->source->writeFile(this->jobPath);
	}
	else {
		std::cout << "Error creating the job folder." << endl;
		throw "Error creating the job folder!";
	}

	this->problemInfo = this->getProblemInfo(DBCon);

	cout << endl;
	cout << "Job #" << this->jobID << " initialized with: " << endl
		<< "\tUser ID: " << this->user->GetUserID() << endl
		<< "\tProblem ID: " << this->problemID << endl;
	cout << endl;

	delete stmt;
	delete res;
}

void Job::compileSource() {
	using boost::format;
	using boost::io::group;

	boost::format fmt = format("g++ -o jobs/%1%/program.exe \"jobs/%2%/%3%\"") % this->jobID % this->jobID % this->source->getFileName().c_str();
	std::string command = fmt.str();

	std::cout << "Job #" << this->jobID << ": compiling source code... ";
	
	this->compilerOutput = exec(command.c_str());

	// Check whether the program has been compiled
	if (!boost::filesystem::exists(this->jobPath.generic_string() + "/program.exe")) {
		std::cout << "Error! Printing compiler output." << std::endl
			<< std::endl << this->compilerOutput << std::endl << std::endl;
		
		this->score = -1;

		throw "Could not compile source code!";
	}
	std::cout << "Ready!" << std::endl;
}

void Job::updateJobTest(sql::Connection *DBCon, int TestID, bool status) {
	sql::Statement *stmt = NULL;
	
	std::string statusString;
	if (status) {
		statusString = "correct";
	}
	else {
		statusString = "failed";
	}

	try {
		sql::PreparedStatement *prepStmt;
		prepStmt = DBCon->prepareStatement(
			"INSERT INTO `job_tests` (`job_id`, `test_id`, `status`) VALUES (?, ?, ?);");

		prepStmt->setInt(1, this->jobID);
		prepStmt->setInt(2, TestID);
		prepStmt->setString(3, statusString);

		prepStmt->execute();
	}
	catch (sql::SQLException &e) {
		cout << "# ERR: SQLException in " << __FILE__;
		cout << "(" << __FUNCTION__ << ") on line " << __LINE__ << endl;
		cout << "# ERR: " << e.what();
		cout << " (MySQL error code: " << e.getErrorCode();
		cout << ", SQLState: " << e.getSQLState() << " )" << endl;
	}
}

void Job::runTests(sql::Connection *DBCon) {
	using boost::format;
	using boost::io::group;

	int total = 0;

	std::queue<JobTest> tests = this->getJobTests(DBCon);
	while (!tests.empty()) {
		JobTest currentTest = tests.front();

		std::cout << "Running test #" << currentTest.testID << "... ";
		currentTest.input->setFileName(this->problemInfo.name + ".in");
		currentTest.input->writeFile(this->jobPath);


		boost::format fmt = format("cd jobs/%1% && ./program.exe") % this->jobID;
		std::string command = fmt.str();

		exec(command.c_str());

		currentTest.output->writeFile(this->jobPath);

		if (compareFiles(
			(this->jobPath / (this->problemInfo.name + ".out")).generic_string(),
			(this->jobPath / currentTest.output->getFileName()).generic_string())) {
			std::cout << "Passed!" << std::endl;

			this->updateJobTest(DBCon, currentTest.testID, true);
			total += currentTest.points;
		}
		else {
			std::cout << "Failed!" << std::endl;
			this->updateJobTest(DBCon, currentTest.testID, false);
		}

		delete currentTest.input;
		delete currentTest.output;

		tests.pop();
	}

	this->score = total;

	cout << "Test running done with score " << total << endl;
}

ProblemInfo Job::getProblemInfo(sql::Connection *DBCon) {
	ProblemInfo info;

	sql::Statement *stmt = NULL;
	sql::ResultSet *res = NULL;

	try {
		std::stringstream query;

		query << "SELECT `name_clean`, `io_method`, `max_time`, `max_mem`, `points` FROM `problems` WHERE `id` = "
			<< this->problemID;

		stmt = DBCon->createStatement();
		res = stmt->executeQuery(query.str());
		while (res->next()) {
			info.problemID = this->problemID;
			info.name = res->getString("name_clean");

			info.isFileInput = res->getString("io_method") == "file";
			info.maxTime = res->getInt("max_time");
			info.maxMemory = res->getInt("max_mem");

			info.points = res->getInt("points");
		}
	}
	catch (sql::SQLException &e) {
		cout << "# ERR: SQLException in " << __FILE__;
		cout << "(" << __FUNCTION__ << ") on line " << __LINE__ << endl;
		cout << "# ERR: " << e.what();
		cout << " (MySQL error code: " << e.getErrorCode();
		cout << ", SQLState: " << e.getSQLState() << " )" << endl;
	}

	return info;
}

std::queue<JobTest> Job::getJobTests(sql::Connection *DBCon) {
	std::queue<JobTest> tests;

	sql::Statement *stmt = NULL;
	sql::ResultSet *res = NULL;

	try {
		std::stringstream query;

		query << "SELECT `id`, `input_file_id`, `output_file_id`, `points` FROM `problem_tests` WHERE `problem_id` = "
			<< this->problemID;

		stmt = DBCon->createStatement();
		res = stmt->executeQuery(query.str());
		while (res->next()) {
			JobTest test;

			test.testID = res->getInt("id");
			test.input = new File(DBCon, res->getInt("input_file_id"));
			test.output = new File(DBCon, res->getInt("output_file_id"));
			test.points = res->getInt("points");

			tests.push(test);
		}
	}
	catch (sql::SQLException &e) {
		cout << "# ERR: SQLException in " << __FILE__;
		cout << "(" << __FUNCTION__ << ") on line " << __LINE__ << endl;
		cout << "# ERR: " << e.what();
		cout << " (MySQL error code: " << e.getErrorCode();
		cout << ", SQLState: " << e.getSQLState() << " )" << endl;
	}

	return tests;
}

void Job::updateDatabase(sql::Connection *DBCon) {
	// Send the user a notification
	this->user->SendNotification(DBCon, "Rezolvarea problemei #" + std::to_string(this->problemID) +
		" a fost evaluata cu " + std::to_string(this->score) + " puncte.");

	// Reward the user with coins if they have solved the problem for the first time
	if (!this->user->SolvedProblem(DBCon, this->problemID) && this->score == 100) {
		this->user->ChangeCoins(DBCon, this->problemInfo.points);
		this->user->SendNotification(DBCon, "Ai primit " + std::to_string(this->problemInfo.points) +
			" pentru rezolvarea corecta a problemei #" + std::to_string(this->problemID));
	}

	sql::Statement *stmt = NULL;

	try {
		sql::PreparedStatement *prepStmt;
		prepStmt = DBCon->prepareStatement(
			"UPDATE `jobs` SET `done` = 1, `score` = ?, `compiler_message` = ?, `done_time` = NOW() WHERE `id` = ?");

		prepStmt->setInt(1, this->score);
		prepStmt->setString(2, this->compilerOutput);
		prepStmt->setInt(3, this->jobID);

		prepStmt->execute();
	}
	catch (sql::SQLException &e) {
		cout << "# ERR: SQLException in " << __FILE__;
		cout << "(" << __FUNCTION__ << ") on line " << __LINE__ << endl;
		cout << "# ERR: " << e.what();
		cout << " (MySQL error code: " << e.getErrorCode();
		cout << ", SQLState: " << e.getSQLState() << " )" << endl;
	}
}

Job::~Job() {
	if (boost::filesystem::exists(this->jobPath)) {
		if (boost::filesystem::remove_all(this->jobPath)) {
			cout << "Successfully deleted job folder." << endl;
		}
		else {
			cout << "Could not delete job folder." << endl;
		}
	}
}
