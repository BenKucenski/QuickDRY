using System;
using System.Collections;
using System.Configuration;
using System.Data.SqlClient;


namespace QuickDRY
{
    /// <summary>
    /// Summary description for SQLCore
    /// </summary>
    public class SQLCore
    {
        private static Hashtable Connections;

        public SQLCore()
        {
        }
        public static SQLResult Query(String SQL, Hashtable Parameters, String ConnectionName)
        {
            return Query(SQL, Parameters, ConnectionName, true);
        }

        public static SQLResult Query(String SQL, Hashtable Parameters, String ConnectionName, bool HaltOnError)
        {
            if (Connections == null)
            {
                Connections = new Hashtable();
            }
            if (!Connections.ContainsKey(ConnectionName))
            {
                Connections.Add(ConnectionName, new SqlConnection(ConfigurationManager.ConnectionStrings[ConnectionName].ConnectionString));
            }

            SqlDataReader reader = null;
            SQLResult result = new SQLResult();
            result.ConnectionName = ConnectionName;
            result.Query = SQL;
            result.Parameters = Parameters;

            try
            {
                SqlConnection conn = Connections[ConnectionName] as SqlConnection;
                if (conn.State != System.Data.ConnectionState.Open)
                {
                    conn.Open();
                }

                SqlCommand cmd = new SqlCommand(SQL, conn);
                if (Parameters != null)
                {
                    foreach (string param in Parameters.Keys)
                    {
                        if (Parameters[param] == null || Parameters[param].ToString() == "null")
                        {
                            cmd.Parameters.Add(new SqlParameter(param, DBNull.Value));
                        }
                        else
                        {
                            cmd.Parameters.Add(new SqlParameter(param, Parameters[param]));
                        }
                    }
                }
                reader = cmd.ExecuteReader();

                // write each record
                while (reader.Read())
                {
                    Hashtable row = new Hashtable();
                    for (int col = 0; col < reader.FieldCount; col++)
                    {
                        row[reader.GetName(col)] = reader.GetValue(col);
                    }
                    result.Results.Add(row);
                }


                reader.Close();
                conn.Close();

            }
            catch (Exception ex)
            {
                result.Error = ex.ToString().Replace("\r\n", "<br/>");
                if(HaltOnError)
                {
                    Debug.Halt(result);
                }
            }
            finally
            {

            }

            return result;
        }
        public static SQLResult Execute(String SQL, Hashtable Parameters, String ConnectionName)
        {
            return Execute(SQL, Parameters, ConnectionName, true);
        }

        public static SQLResult Execute(String SQL, Hashtable Parameters, String ConnectionName, bool HaltOnError)
        {
            if (Connections == null)
            {
                Connections = new Hashtable();
            }
            if (!Connections.ContainsKey(ConnectionName))
            {
                Connections.Add(ConnectionName, new SqlConnection(ConfigurationManager.ConnectionStrings[ConnectionName].ConnectionString));
            }

            SQLResult result = new SQLResult();
            result.ConnectionName = ConnectionName;
            result.Query = SQL;
            result.Parameters = Parameters;

            try
            {
                SqlConnection conn = Connections[ConnectionName] as SqlConnection;
                if (conn.State != System.Data.ConnectionState.Open)
                {
                    conn.Open();
                }

                SqlCommand cmd = new SqlCommand(SQL, conn);
                if (Parameters != null)
                {
                    foreach (string param in Parameters.Keys)
                    {
                        if (Parameters[param] == null || Parameters[param].ToString() == "null")
                        {
                            cmd.Parameters.Add(new SqlParameter(param, DBNull.Value));
                        }
                        else
                        {
                            cmd.Parameters.Add(new SqlParameter(param, Parameters[param]));
                        }
                    }
                }
                cmd.ExecuteNonQuery();
                conn.Close();

            }
            catch (Exception ex)
            {
                result.Error = ex.ToString().Replace("\r\n","<br/>");
                if (HaltOnError)
                {
                    Debug.Halt(result);
                }
            }
            finally
            {

            }

            return result;
        }

        public static SQLResult ExecuteStoredProc(String SQL, Hashtable Parameters, String ConnectionName)
        {
            return ExecuteStoredProc(SQL, Parameters, ConnectionName, true);
        }

        public static SQLResult ExecuteStoredProc(String SQL, Hashtable Parameters, String ConnectionName, bool HaltOnError)
        {
            if (Connections == null)
            {
                Connections = new Hashtable();
            }
            if (!Connections.ContainsKey(ConnectionName))
            {
                Connections.Add(ConnectionName, new SqlConnection(ConfigurationManager.ConnectionStrings[ConnectionName].ConnectionString));
            }

            SQLResult result = new SQLResult();
            result.ConnectionName = ConnectionName;
            result.Query = SQL;
            result.Parameters = Parameters;

            try
            {
                SqlConnection conn = Connections[ConnectionName] as SqlConnection;
                if (conn.State != System.Data.ConnectionState.Open)
                {
                    conn.Open();
                }

                SqlCommand cmd = new SqlCommand(SQL, conn);
                cmd.CommandType = System.Data.CommandType.StoredProcedure;

                if (Parameters != null)
                {
                    foreach (string param in Parameters.Keys)
                    {
                        if (Parameters[param] == null || Parameters[param].ToString() == "null")
                        {
                            cmd.Parameters.Add(new SqlParameter(param, DBNull.Value));
                        }
                        else
                        {
                            cmd.Parameters.Add(new SqlParameter(param, Parameters[param]));
                        }
                    }
                }
                cmd.ExecuteNonQuery();
                conn.Close();

            }
            catch (Exception ex)
            {
                result.Error = ex.ToString().Replace("\r\n", "<br/>");
                if (HaltOnError)
                {
                    Debug.Halt(result);
                }
            }
            finally
            {

            }

            return result;
        }
    }
}