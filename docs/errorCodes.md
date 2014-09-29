Error codes
===============

# 1xxx - API errors

## 11xx - Parameters

### 1101 - Session ID invalid
The provided session id _sessionId_ is invalid. This string is validated by a [a-zA-Z0-9]{26} regexp.


## 19xx - Internal model errors
### 191x - Session.model.php
#### 1911 - SessionId is not defined
The session ID is not defined inside the model. This means there was an error during the __construct phase, but the session ID was called afterwards.
#### 1912 - User does not exist
Error caused when trying to authenticate a non-existent user in the current session.

# 2xxx - Database errors

