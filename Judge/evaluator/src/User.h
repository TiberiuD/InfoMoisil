#ifndef USER_H_INCLUDED
#define USER_H_INCLUDED

#include "mysql_connection.h"

#include <cppconn/driver.h>
#include <cppconn/exception.h>
#include <cppconn/resultset.h>
#include <cppconn/statement.h>
#include <cppconn/prepared_statement.h>

#include <string>

class User {
private:
	int userID;
public:
	User(sql::Connection *DBCon, int userID);
	~User();

	int GetUserID();
	bool SolvedProblem(sql::Connection *DBCon, int problemID);
	void ChangeCoins(sql::Connection *DBCon, int amount);
	void SendNotification(sql::Connection *DBCon, std::string message);
};

#endif