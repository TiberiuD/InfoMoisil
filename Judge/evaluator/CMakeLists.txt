cmake_minimum_required(VERSION 2.8)

project(judge CXX)

file(GLOB_RECURSE sources
  src/*.cpp src/*.h
)

include_directories(src/)

# Find dependencies
find_package(MySQL 5.6 REQUIRED)
find_package(MySQLConnectorCPP REQUIRED)
find_package(Boost COMPONENTS system filesystem date_time thread REQUIRED)
find_package(yaml-cpp PATHS yaml-cpp-build)

add_executable(${PROJECT_NAME} ${sources})

include(CheckCXXCompilerFlag)
CHECK_CXX_COMPILER_FLAG("-std=c++11" COMPILER_SUPPORTS_CXX11)
CHECK_CXX_COMPILER_FLAG("-std=c++0x" COMPILER_SUPPORTS_CXX0X)
if(COMPILER_SUPPORTS_CXX11)
    set(CMAKE_CXX_FLAGS "${CMAKE_CXX_FLAGS} -std=c++11")
elseif(COMPILER_SUPPORTS_CXX0X)
    set(CMAKE_CXX_FLAGS "${CMAKE_CXX_FLAGS} -std=c++0x")
else()
        message(STATUS "The compiler ${CMAKE_CXX_COMPILER} has no C++11 support. Please use a different C++ compiler.")
endif()

# Link dependencies if found
if (MYSQL_FOUND)
  include_directories(${MYSQL_INCLUDE_DIR})
  target_link_libraries (${PROJECT_NAME} ${MYSQL_CLIENT_LIBS})
endif (MYSQL_FOUND)

if (MYSQLCONNECTORCPP_FOUND)
  include_directories(${MYSQLCONNECTORCPP_INCLUDE_DIR})
  target_link_libraries (${PROJECT_NAME} ${MYSQLCONNECTORCPP_LIBRARY})
endif (MYSQLCONNECTORCPP_FOUND)

if (Boost_FOUND)
  include_directories(${Boost_INCLUDE_DIRS})
  target_link_libraries (${PROJECT_NAME} ${Boost_LIBRARIES} 
	  ${Boost_SYSTEM_LIBRARY}
	  ${Boost_FILESYSTEM_LIBRARY}
	  ${Boost_DATE_TIME_LIBRARY}
	  ${Boost_THREAD_LIBRARY})
endif (Boost_FOUND)

include_directories(${YAML_CPP_INCLUDE_DIR})
target_link_libraries (${PROJECT_NAME} ${YAML_CPP_LIBRARIES})

cotire(${PROJECT_NAME})
