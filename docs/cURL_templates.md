# Production
```
curl https://api.pantrysync.pro/api/v1/authtest \
-H 'Accept: application/json' \
-H 'X-RestockApiToken: anything' \
-H 'X-RestockUserApiToken: Go6RJhV5fRZoHPwgbFWrGJbElzJtdilERJ7alE+EpiA=' \
-G \
&& echo
```
___
# VM
___
# UserController
#### Check if email exists
```
curl http://api.cpsc4900.local/api/v1/user/{email} \
--head \
-H "Accept: application/json" \
-H "X-RestockApiToken: anything" \
&& echo
```

#### Create new user
```
curl http://api.cpsc4900.local/api/v1/user \
-H "Accept: application/json" \
-H "X-RestockApiToken: anything" \
-d "username=Vin" \
-d "password=rootroot" \
-d "email=fwp678@mocs.utc.edu" \
&& echo
```

### Login
```
curl http://api.cpsc4900.local/api/v1/session \
-H "Accept: application/json" \
-H "X-RestockApiToken: anything" \
-d "password=rootroot" \
-d "email=fwp678@mocs.utc.edu" \
&& echo
```

### AuthTest
```
curl http://api.cpsc4900.local/api/v1/authtest \
-G \
-H "Accept: application/json" \
-H "X-RestockApiToken: anything" \
-H "X-RestockUserApiToken: {token}" \
&& echo
```

### Logout
```
curl http://api.cpsc4900.local/api/v1/session \
-X "DELETE" \
-H "Accept: application/json" \
-H "X-RestockApiToken: anything" \
-H "X-RestockUserApiToken: {token}" \
&& echo
```

#### Update User
```
curl http://api.cpsc4900.local/api/v1/user/{user_id} \
-X "PUT" \
-H "Accept: application/json" \
-H "Content-Type: application/json" \
-H "X-RestockApiToken: anything" \
-H "X-RestockUserApiToken: {token}" \
-d '{"new_username":"Vin","new_password":"rootroot","password":"rootroot"}' \
&& echo
```

### Delete User
```
curl http://api.cpsc4900.local/api/v1/user/{user_id} \
-X "DELETE" \
-H "Accept: application/json" \
-H "X-RestockApiToken: anything" \
-H "X-RestockUserApiToken: {token}" \
&& echo
```

___
# GroupController
#### Create Group
curl http://api.cpsc4900.local/api/v1/group \
-H "Accept: application/json" \
-H "X-RestockApiToken: anything" \
-H "X-RestockUserApiToken: {token}" \
-d "name={group_name}" \
&& echo

#### Get Group Details
```
curl http://api.cpsc4900.local/api/v1/group/{group_id} \
-H "Accept: application/json" \
-H "X-RestockApiToken: anything" \
-H "X-RestockUserApiToken: {token}"
&& echo
```

#### Update Group
```
curl http://api.cpsc4900.local/api/v1/group/{group_id} \
-X "PUT" \
-H "Accept: application/json" \
-H "X-RestockApiToken: anything" \
-H "X-RestockUserApiToken: {token}"
-d "{{id:{name:{group_name}}}" \
&& echo
```

#### Delete Group
```
curl http://api.cpsc4900.local/api/v1/group/{group_id} \
-X "DELETE" \
-H "Accept: application/json" \
-H "X-RestockApiToken: anything" \
-H "X-RestockUserApiToken: {token}" \
&& echo
```

___
# Item Controller
#### Create Item
```
curl http://api.cpsc4900.local/api/v1/group/{group_id}/item \
-H "Accept: application/json" \
-H "X-RestockApiToken: anything" \
-H "X-RestockUserApiToken: {token}" \
-d "name={name}" \
-d "description={description}"
-d "category={category#color}" \
-d "pantry_quantity={quantity}" \
-d "minimum_threshold={quantity}" \
-d "auto_add_to_shopping_list={boolean}" \
-d "shopping_list_quantity={quantity}" \
-d "dont_add_to_pantry_on_purchase={boolean}" \
&& echo
```

#### Update Item
```
curl http://api.cpsc4900.local/api/v1/group/{group_id}/item/{item_id} \
-X "PUT" \
-H "Accept: application/json" \
-H "Content-Type: application/json" \
-H "X-RestockApiToken: anything" \
-H "X-RestockUserApiToken: {token}" \
-d '{ "name": "{name}", "description": "{description}", "category": "{category#color}", "pantry_quantity": "{quantity}", "minimum_threshold": "{quantity}", "auto_add_to_shopping_list": "{boolean}", "shopping_list_quantity": "{quantity}", "dont_add_to_pantry_on_purchase": "{boolean}" }'
```

#### Delete Item
```
curl http://api.cpsc4900.local/api/v1/group/{group_id}/item/{item_id} \
-X "DELETE" \
-H "Accept: application/json" \
-H "X-RestockApiToken: anything" \
-H "X-RestockUserApiToken: {token}" \
&& echo
```

___
