#ifndef UTILS_H_INCLUDED
#define UTILS_H_INCLUDED

// Standard C++ includes
#include <iostream>
#include <fstream>
#include <iterator>
#include <string>
#include <cstdio>

// Libraries
#include "mysql_connection.h"
#include <cppconn/driver.h>
#include <cppconn/exception.h>
#include <cppconn/resultset.h>
#include <cppconn/statement.h>
#include <boost/date_time/posix_time/posix_time.hpp>
#include <boost/thread/thread.hpp> 
#include <boost/format.hpp>


bool compareFiles(const std::string& p1, const std::string& p2);
std::string exec(const char* cmd);
std::string getOsName();
void sleep(int milliseconds);

#endif