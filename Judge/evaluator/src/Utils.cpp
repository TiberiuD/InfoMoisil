#include "Utils.h"

bool compareFiles(const std::string& p1, const std::string& p2) {
	std::ifstream f1(p1, std::ifstream::binary | std::ifstream::ate);
	std::ifstream f2(p2, std::ifstream::binary | std::ifstream::ate);

	if (f1.fail() || f2.fail()) {
		return false; //file problem
	}

	if (f1.tellg() != f2.tellg()) {
		return false; //size mismatch
	}

	//seek back to beginning and use std::equal to compare contents
	f1.seekg(0, std::ifstream::beg);
	f2.seekg(0, std::ifstream::beg);
	return std::equal(std::istreambuf_iterator<char>(f1.rdbuf()), \
		std::istreambuf_iterator<char>(), \
		std::istreambuf_iterator<char>(f2.rdbuf()));
}

std::string exec(const char* cmd) {
	using boost::format;
	using boost::io::group;

	char buffer[128];
	std::string result = "";

	boost::format fmt = format("%1% 2>&1") % cmd;
	std::string command = fmt.str();

#ifdef _WIN32
	std::shared_ptr<FILE> pipe(_popen(command.c_str(), "r"), _pclose);
#else
	std::shared_ptr<FILE> pipe(popen(command.c_str(), "r"), pclose);
#endif
	if (!pipe) throw std::runtime_error("popen() failed!");
	while (!feof(pipe.get())) {
		if (fgets(buffer, 128, pipe.get()) != NULL)
			result += buffer;
	}
	return result;
}

std::string getOsName() {
#ifdef _WIN32
	return "Windows 32-bit";
#elif _WIN64
	return "Windows 64-bit";
#elif __unix || __unix__
	return "Unix";
#elif __APPLE__ || __MACH__
	return "Mac OSX";
#elif __linux__
	return "Linux";
#elif __FreeBSD__
	return "FreeBSD";
#else
	return "Other";
#endif
}

void sleep(int milliseconds) {
	boost::this_thread::sleep_for(boost::chrono::milliseconds(milliseconds));
}