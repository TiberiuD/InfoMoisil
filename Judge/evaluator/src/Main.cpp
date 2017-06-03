/* Standard C++ includes */
#include <cstdlib>
#include <iostream>
#include <fstream>
#include <ctime>
#include <vector>
#include <queue>

#include "Config.h"
#include "Version.h"
#include "Job.h"
#include "File.h"
#include "Utils.h"

/*
Include directly the different
headers from cppconn/ and mysql_driver.h + mysql_util.h
(and mysql_connection.h). This will reduce your build time!
*/
#include "mysql_connection.h"

#include <cppconn/driver.h>
#include <cppconn/exception.h>
#include <cppconn/resultset.h>
#include <cppconn/statement.h>


using namespace std;

int main(void) {
	cout << "Judge version " << programVersion
		<< " (" << programStage << ", " << getOsName() << "). Initializing..." << endl;

	cout << endl;

	// Read program configuration info
	config configInfo;
	if (!readConfigFile(configInfo)) {
		cout << "FATAL ERROR: Could not read program configuration. Exiting..." << endl;
		return EXIT_FAILURE;
	}

	// Set program shutdown flag to false
	bool doShutdown = false;

	// Initialize database connection
	sql::Driver *driver = NULL;
	sql::Connection *con = NULL;

	try {
		/* Create a connection */
		driver = get_driver_instance();
		con = driver->connect(configInfo.db_hostname + ":" + configInfo.db_port, configInfo.db_username, configInfo.db_password);
		/* Connect to the MySQL test database */
		con->setSchema(configInfo.db_database);
	}
	catch (sql::SQLException &e) {
		cout << "# ERR: SQLException in " << __FILE__;
		cout << "(" << __FUNCTION__ << ") on line " << __LINE__ << endl;
		cout << "# ERR: " << e.what();
		cout << " (MySQL error code: " << e.getErrorCode();
		cout << ", SQLState: " << e.getSQLState() << " )" << endl;

		doShutdown = true;
	}
	
	// Enter main loop
	std::queue<Job*> jobs;
	while (!doShutdown) {
		// If there aren't any jobs to process, query the database
		if (jobs.empty()) {
			cout << "Job queue is empty. Querying the database..." << endl;

			std::queue<int> jobIDs = getJobs(con, configInfo.queue_max_size);
			cout << "\t" << jobIDs.size() << " job(s) retrieved!" << endl;

			while (!jobIDs.empty()) {
				Job *temp = new Job(con, jobIDs.front());
				jobIDs.pop();

				jobs.push(temp);
			}
		}
		else {
			cout << "Waiting for jobs to complete..." << endl;
		}

		// Process all the jobs in the queue
		while (!jobs.empty()) {
			Job *currentJob = jobs.front();

			try {
				currentJob->compileSource();
				currentJob->runTests(con);
			}
			catch (const char * ex) {
				cout << "Exception caught: " << ex << endl;
			}

			currentJob->updateDatabase(con);			

			delete currentJob;
			
			jobs.pop();
		}

		sleep(configInfo.database_refresh_time);
	}

	cout << endl;

	// Initialize program shutdown
	delete con;

	return EXIT_SUCCESS;
}