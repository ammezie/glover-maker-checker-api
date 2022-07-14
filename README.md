## Assumptions
Based on the task description, I went with the following assumptions:

* The endpoints will be accessed by only admin users.
* Used a single `users` table for all users and having an `is_admin` column to indicate an admin.

## Endpoints
* `POST` `/api/auth/register`: registers a new admin users.
* `POST` `/api/auth/login`: log an admin user in.
* `POST` `/api/auth/logout`: log an admin user out.
* `GET` `/api/requests`: a list of pending requests.
* `POST` `/api/requests`: create a new request. It accepts `type` and `data` (array of user details to be created, updated or deleted) as payload. Depending on the request type, `data` must contain the following:
  * `create`: `first_name`, `last_name`, `email`, and `password`.
  * `update`: `user_id`,`first_name`, `last_name`, and `email`.
  * `delete`: `user_id`.
* `POST` `/api/requests/{id}/approve`: approve a pending request. It accepts the ID of the request to approve.
* `POST` `/api/requests/{id}/decline`: decline a pending request. It accepts the ID of the request to decline.
