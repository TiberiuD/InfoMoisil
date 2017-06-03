cmake_minimum_required(VERSION 2.8)

project("InfoMoisil Judge")

# Build mode debug/release?
set(CMAKE_BUILD_TYPE Debug)

# Set build directory
set(CMAKE_ARCHIVE_OUTPUT_DIRECTORY ${CMAKE_BINARY_DIR}/lib)
set(CMAKE_LIBRARY_OUTPUT_DIRECTORY ${CMAKE_BINARY_DIR}/lib)
set(CMAKE_RUNTIME_OUTPUT_DIRECTORY ${CMAKE_BINARY_DIR}/bin)

# Set the CMake module directory
set(CMAKE_MODULE_PATH ${CMAKE_MODULE_PATH} "${CMAKE_SOURCE_DIR}/cmake/")

# https://github.com/sakra/cotire
include(cotire)

# Set the global include directories
include_directories(.)

# Set static library linking in Visual Studio
# if (MSVC)
	# set (CMAKE_CXX_FLAGS_MINSIZEREL     "${CMAKE_CXX_FLAGS_MINSIZEREL} /MT")
	# set (CMAKE_CXX_FLAGS_RELWITHDEBINFO "${CMAKE_CXX_FLAGS_RELWITHDEBINFO} /MT")
	# set (CMAKE_CXX_FLAGS_RELEASE        "${CMAKE_CXX_FLAGS_RELEASE} /MT")
	# set (CMAKE_CXX_FLAGS_DEBUG          "${CMAKE_CXX_FLAGS_DEBUG} /MTd")

	# set (CMAKE_C_FLAGS_MINSIZEREL       "${CMAKE_C_FLAGS_MINSIZEREL} /MT")
	# set (CMAKE_C_FLAGS_RELWITHDEBINFO   "${CMAKE_C_FLAGS_RELWITHDEBINFO} /MT")
	# set (CMAKE_C_FLAGS_RELEASE          "${CMAKE_C_FLAGS_RELEASE} /MT")
	# set (CMAKE_C_FLAGS_DEBUG            "${CMAKE_C_FLAGS_DEBUG} /MTd")
	
	# add_definitions(-D__WIN32__)
# endif()

add_subdirectory(evaluator)
