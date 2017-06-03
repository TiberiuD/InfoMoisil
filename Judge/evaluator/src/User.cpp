#include "User.h"

User::User(sql::Connection *DBCon, int userID) {
	this->userID = userID;

	sql::PreparedStatement *prep_stmt;
	sql::ResultSet *res;

	try {
		prep_stmt = DBCon->prepareStatement("SELECT `id`, `coins` FROM `users` WHERE `id` = ?;");
		prep_stmt->setInt(1, this->userID);
		res = prep_stmt->executeQuery();
	}
	catch (sql::SQLException &e) {
		std::cout << "# ERR: SQLException in " << __FILE__;
		std::cout << "(" << __FUNCTION__ << ") on line " << __LINE__ << std::endl;
		std::cout << "# ERR: " << e.what();
		std::cout << " (MySQL error code: " << e.getErrorCode();
		std::cout << ", SQLState: " << e.getSQLState() << " )" << std::endl;
	}

	if (res->rowsCount() == 0)
		throw ("User with ID " + std::to_string(this->userID) + " doesn't exist!");

	delete prep_stmt;
	delete res;
}

User::~User() {

}

int User::GetUserID() {
	return this->userID;
}

bool User::SolvedProblem(sql::Connection *DBCon, int problemID) {
	sql::PreparedStatement *prep_stmt;
	sql::ResultSet *res;

	int score = 0;

	try {
		prep_stmt = DBCon->prepareStatement("SELECT `score` FROM `jobs` WHERE `user_id` = ? AND `problem_id` = ? ORDER BY `score` DESC LIMIT 1;");
		prep_stmt->setInt(1, this->userID);
		prep_stmt->setInt(2, problemID);
		res = prep_stmt->executeQuery();

		if (res->rowsCount() == 0) {
			delete prep_stmt;
			delete res;

			return false;
		}


		while (res->next()) {
			score = res->getInt("score");
		}
	}
	catch (sql::SQLException &e) {
		std::cout << "# ERR: SQLException in " << __FILE__;
		std::cout << "(" << __FUNCTION__ << ") on line " << __LINE__ << std::endl;
		std::cout << "# ERR: " << e.what();
		std::cout << " (MySQL error code: " << e.getErrorCode();
		std::cout << ", SQLState: " << e.getSQLState() << " )" << std::endl;
	}


	delete prep_stmt;
	delete res;

	return (score >= 100);
}

void User::ChangeCoins(sql::Connection *DBCon, int amount) {
	sql::PreparedStatement *prep_stmt;

	try {
		prep_stmt = DBCon->prepareStatement("UPDATE `users` SET `coins` = (`coins` + ?) WHERE `id` = ? LIMIT 1");
		prep_stmt->setInt(2, this->userID);
		prep_stmt->setInt(1, amount);
		prep_stmt->executeUpdate();
	}
	catch (sql::SQLException &e) {
		std::cout << "# ERR: SQLException in " << __FILE__;
		std::cout << "(" << __FUNCTION__ << ") on line " << __LINE__ << std::endl;
		std::cout << "# ERR: " << e.what();
		std::cout << " (MySQL error code: " << e.getErrorCode();
		std::cout << ", SQLState: " << e.getSQLState() << " )" << std::endl;
	}

	delete prep_stmt;
}

void User::SendNotification(sql::Connection *DBCon, std::string message) {
	sql::PreparedStatement *prep_stmt;

	try {
		prep_stmt = DBCon->prepareStatement("INSERT INTO `notifications` (`user_id`, `time`, `message`) VALUES (?, NOW(), ?)");
		prep_stmt->setInt(1, this->userID);
		prep_stmt->setString(2, message);
		prep_stmt->executeUpdate();
	}
	catch (sql::SQLException &e) {
		std::cout << "# ERR: SQLException in " << __FILE__;
		std::cout << "(" << __FUNCTION__ << ") on line " << __LINE__ << std::endl;
		std::cout << "# ERR: " << e.what();
		std::cout << " (MySQL error code: " << e.getErrorCode();
		std::cout << ", SQLState: " << e.getSQLState() << " )" << std::endl;
	}

	delete prep_stmt;
}
